<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * "Ask ΣX": answers free-form questions with Gemma 4, grounded in the
 * precomputed per-kecamatan analysis (public/data/analysis.json).
 */
class AskController extends Controller
{
    // Compact per-kecamatan table sent with every question: column => path in the zone record
    private const COLUMNS = [
        'id' => 'id',
        'name' => 'name',
        'verdict' => 'gemma.verdict',
        'growth_score' => 'gemma.growth_score',
        'headline' => 'gemma.headline',
        'area_km2' => 'signals.area_km2',
        'built_pct_2019' => 'signals.dynamic_world_built_share_pct.from',
        'built_pct_2025' => 'signals.dynamic_world_built_share_pct.to',
        'bare_delta_pp' => 'signals.dynamic_world_bare_share_pct.delta_pp',
        'ndbi_delta' => 'signals.ndbi.delta',
        'night_lights_change_pct' => 'signals.night_lights_radiance.change_pct',
        'land_temp_delta_c' => 'signals.land_surface_temp_c.delta',
        'population_change_pct_2020_2025' => 'signals.population_ghsl_modelled.change_pct',
        'hotel_survival_pct' => 'signals.hotels.hotel_survival_pct',
        'fnb_growth_pct' => 'signals.osm_business_counts.fnb.growth_pct',
        'retail_growth_pct' => 'signals.osm_business_counts.retail.growth_pct',
        'office_growth_pct' => 'signals.osm_business_counts.office.growth_pct',
        'built_pct_2028_outlook' => 'gemma.outlook_2028.built_share_2028_pct',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'question' => 'required|string|max:500',
            'zone' => 'nullable|string|max:40',
        ]);

        set_time_limit(280); // Gemma on the free tier can take up to ~2 min under load

        $context = $this->context($data['zone'], $data['question']);
        $focus = $data['zone'] ? "The user is currently looking at kecamatan id '{$data['zone']}'.\n" : '';

        $prompt = <<<PROMPT
        You are SIGMA X, a spatial-economics analyst for Kota Batam, Indonesia. Answer using ONLY the
        evidence below (satellite indices 2019->2025, Places/OSM ground truth 2024->2026, and your own
        earlier per-kecamatan verdicts). Cite numbers. If the evidence cannot answer, say so.
        Answer in the language of the question (Bahasa Indonesia or English), max 150 words, plain text.
        {$focus}
        EVIDENCE (JSON): "zone_table" has one row per kecamatan (column names in "columns");
        "zone_detail" has the full evidence and your reasoning for the kecamatan in focus;
        "batam_wide_change" is the city-wide change every kecamatan is compared against.
        {$context}

        QUESTION: {$data['question']}
        PROMPT;

        $cacheKey = 'ask:'.sha1(mb_strtolower(trim($data['question'])).'|'.($data['zone'] ?? ''));
        if ($cached = Cache::get($cacheKey)) {
            return response()->json($cached + ['cached' => true]);
        }

        // Live Q&A: Gemma 4 26B MoE first (faster), 31B as fallback. Gemma 4 thinks before
        // answering and thought tokens count toward the output limit, so the limit is generous.
        $retryIn = null;
        foreach (['gemma-4-26b-a4b-it', config('services.gemini.model')] as $model) {
            try {
                $response = Http::timeout(75)
                    ->withHeaders(['x-goog-api-key' => config('services.gemini.key')])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                        'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                        'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 4096],
                    ]);
            } catch (ConnectionException $e) {
                Log::warning("Ask ΣX: {$model} unreachable", ['error' => $e->getMessage()]);

                continue;
            }

            $answer = trim(collect($response->json('candidates.0.content.parts', []))
                ->reject(fn ($p) => $p['thought'] ?? false)
                ->pluck('text')->implode(''));

            if ($response->successful() && $answer !== '') {
                $result = ['answer' => $answer, 'model' => $model];
                Cache::put($cacheKey, $result, now()->addDay());

                return response()->json($result);
            }

            Log::warning("Ask ΣX: {$model} failed", ['status' => $response->status(), 'error' => $response->json('error.message')]);

            // 429 = free-tier per-minute quota used up; Google says how long until it refills ("40s")
            if ($response->status() === 429) {
                $wait = (int) collect($response->json('error.details', []))->pluck('retryDelay')->filter()->first();
                $retryIn = min($retryIn ?? PHP_INT_MAX, max($wait, 5));
            }
        }

        if ($retryIn) {
            return response()->json(['error' => "Gemma's free quota for this minute is used up. Try again in about {$retryIn} seconds."], 429);
        }

        return response()->json(['error' => 'Gemma is busy right now, try again in a moment.'], 503);
    }

    /**
     * The free tier allows 16k input tokens per model per minute, so the evidence stays small:
     * one table row per kecamatan, plus full detail only for the kecamatan the user is looking
     * at or names in the question (at most 3).
     */
    private function context(?string $zone, string $question): string
    {
        $analysis = Cache::remember('sigmax-analysis', 600,
            fn () => json_decode(file_get_contents(public_path('data/analysis.json')), true));
        $zones = collect($analysis['zones'])->values();
        $q = mb_strtolower($question);

        $detail = $zones
            ->filter(fn ($z) => $z['id'] === $zone || str_contains($q, mb_strtolower($z['name'])))
            ->sortByDesc(fn ($z) => $z['id'] === $zone)
            ->take(3)
            ->map(fn ($z) => [
                'id' => $z['id'],
                'name' => $z['name'],
                'verdict' => $z['gemma']['verdict'] ?? null,
                'score' => $z['gemma']['growth_score'] ?? null,
                'reasoning' => $z['gemma']['reasoning'] ?? null,
                'outlook_2028' => $z['gemma']['outlook_2028'] ?? null,
                'recommendation' => $z['gemma']['recommendation'] ?? null,
                'signals' => Arr::except($z['signals'], ['batam_wide_change']),
            ])->values();

        return json_encode([
            'city' => Arr::except($analysis['city'], ['_model']),
            'batam_wide_change' => $zones->first()['signals']['batam_wide_change'] ?? null,
            'zone_table' => [
                'columns' => array_keys(self::COLUMNS),
                'rows' => $zones->map(fn ($z) => array_map(fn ($path) => data_get($z, $path), array_values(self::COLUMNS))),
            ],
            'zone_detail' => $detail,
        ], JSON_UNESCAPED_UNICODE);
    }
}
