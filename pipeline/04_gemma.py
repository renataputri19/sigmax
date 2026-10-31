"""Step 4: Gemma 4 as the analyst.

For each kecamatan Gemma receives the structured signals (satellite + ground
truth) as text, plus three images (true colour BASE_YEAR, true colour
LATEST_YEAR, built-up change map), and must reconcile them into a verdict,
a growth score, an outlook and a recommendation. A second call ranks all
zones and writes the city-level brief. Output: public/data/analysis.json
"""
import concurrent.futures as cf
import json
import re
import time

import numpy as np
from google import genai
from google.genai import types

from sigmax_common import (BASE_YEAR, GEMINI_API_KEY, GEMMA_MODEL, IMG, LATEST_YEAR, OUT, WORK,
                           read_json, write_json)

client = genai.Client(api_key=GEMINI_API_KEY)
FALLBACK_MODEL = "gemma-4-26b-a4b-it"  # Gemma 4 MoE: used when the 31B endpoint is overloaded

VERDICTS = {
    "GROWTH_CORRIDOR": "built-up AND activity both rising: realized growth",
    "EARLY_STAGE": "land is being cleared/prepared, structures not finished yet",
    "UNDERBUILT_DEMAND": "activity rising faster than construction: demand ahead of supply",
    "STALLED": "construction rose but activity did not follow",
    "MATURE_STABLE": "already dense, little physical change, activity steady",
    "DECLINING": "activity and/or built signals falling",
}

ANALYST_PROMPT = """You are SIGMA X, a spatial economist analysing development in Batam, Indonesia
(a free-trade zone under BP Batam). You are given evidence for ONE kecamatan:
structured signals below, and three images:
  IMAGE 1 = Sentinel-2 true colour {base}, IMAGE 2 = Sentinel-2 true colour {latest},
  IMAGE 3 = Dynamic World change {base}->{latest} (grey = built both years,
  green = newly built, orange = newly cleared/bare land). Green line = kecamatan boundary.
  IMAGE 4 = Landsat true colour 2016, IMAGE 5 = Landsat true colour 2025 (30 m, coarser than Sentinel-2):
  the DECADE view. Compare IMAGE 4 with IMAGE 5 for the 10-year story.

How to read the signals:
- NDBI up + Dynamic World built share up + radar VV up + Open Buildings up = real structures.
- NDBI up but Dynamic World "bare" up and imagery shows cleared ground = early-stage, NOT realized growth.
- Night lights (VIIRS), hotel survival / new hotels and OSM business counts = economic activity.
- hotel_survival_pct (2024 hotels still operating in Oct 2026) is the most reliable ground signal.
- hotel_type_listings_not_in_2024_scrape mixes genuinely new hotels with ones the 2024 scrape missed: weak signal.
- OSM counts partly reflect volunteer mapping effort. Weigh accordingly.
- 2019 was a dry year: NDVI and land-surface temperature shift in EVERY kecamatan for weather reasons.
  Judge each kecamatan by "change_relative_to_batam" (zone delta minus the Batam-wide delta), not raw deltas.
- Dynamic World is a per-pixel classifier and can swing between years; cross-check it with radar,
  Open Buildings and the images before trusting a drop or jump.
- "decade_2016_2025" is the 10-year view from sensors that existed in both years (Landsat, VIIRS,
  Sentinel-1, Open Buildings 2016->2023, GHSL 2015->2025); "decade_relative_to_batam" removes the
  city-wide drift. OSM counts in 2016 were very incomplete, so OSM decade growth is mostly mapping effort.
- Look at the images yourself and say what you actually see (clearing, new estates, reclamation, roads).

Signals (JSON):
{signals}

Allowed verdicts:
{verdicts}

Respond with ONLY a JSON object, no markdown, with exactly these keys:
{{
 "verdict": one of {verdict_keys},
 "growth_score": integer 0-100 (realized + near-term growth momentum),
 "confidence": "high" | "medium" | "low",
 "headline": "max 14 words, punchy, specific to this kecamatan",
 "image_observations": "2-3 sentences on what IMAGES 1-3 show",
 "reasoning": "3-5 sentences reconciling the numbers with the images",
 "conflicts": "where signals disagree and how you resolved it (or 'none')",
 "evidence": [{{"signal": "...", "reading": "...", "meaning": "..."}}, ... 3 to 5 items],
 "outlook_2028": {{"trajectory": "accelerating" | "steady" | "slowing" | "reversing",
                   "built_share_2028_pct": number (your forecast; the linear baseline is given),
                   "rationale": "1-2 sentences"}},
 "decade_story": "2-3 sentences: how this kecamatan changed 2016 -> 2025, citing decade numbers and IMAGES 4-5",
 "recommendation": "1-2 sentences for BP Batam / Pemko Batam: what to do here",
 "watch_next": "the one signal to monitor next"
}}"""

RANK_PROMPT = """You are SIGMA X. Below are your per-kecamatan assessments for Kota Batam
(verdict, score, headline, key signals). Produce the cross-zone ranking and a city brief.

{zones}

Respond with ONLY a JSON object, no markdown:
{{
 "ranking": [{{"id": "...", "rank": 1, "why": "one sentence"}}, ... all {n} zones, rank 1 = strongest growth],
 "city_headline": "max 16 words",
 "city_brief": "4-6 sentences: where Batam is really growing, what is stalling, and why",
 "city_decade_story": "3-4 sentences: Batam's decade of development 2016 -> 2025, which kecamatan led it",
 "top_opportunity": {{"id": "...", "text": "1-2 sentences"}},
 "top_risk": {{"id": "...", "text": "1-2 sentences"}},
 "method_note": "1 sentence on the limits of this evidence"
}}"""


def pct(a, b):
    return None if a in (None, 0) or b is None else round(100 * (b - a) / abs(a), 1)


def diff(a, b, scale=1, nd=3):
    return None if a is None or b is None else round((b - a) * scale, nd)


def linear_2028(series, key):
    pts = [(int(y), v[key]) for y, v in series.items() if v.get(key) is not None and int(y) <= LATEST_YEAR]
    if len(pts) < 3:
        return None
    x, y = np.array(pts).T
    slope, icpt = np.polyfit(x, y, 1)
    return round(float(np.clip(slope * 2028 + icpt, 0, 1)) * 100, 1)


REF_KEYS = {"ndbi": 1, "ndvi": 1, "dw_built": 100, "dw_bare": 100, "s1_vv": 1, "lst": 1, "viirs": 1}


def city_reference(metas, sat):
    """Area-weighted Batam-wide change BASE_YEAR->LATEST_YEAR, to separate local change from weather/year effects."""
    ref = {}
    for k, scale in REF_KEYS.items():
        vals = {}
        for y in (BASE_YEAR, LATEST_YEAR):
            pts = [(sat[z]["series"][str(y)].get(k), m["area_km2"]) for z, m in metas.items()]
            pts = [(v, w) for v, w in pts if v is not None]
            vals[y] = sum(v * w for v, w in pts) / sum(w for _, w in pts)
        ref[k] = round((vals[LATEST_YEAR] - vals[BASE_YEAR]) * scale, 3) if k != "viirs" else pct(vals[BASE_YEAR], vals[LATEST_YEAR])
    return ref


def relative(sig, ref):
    zone = {"ndbi": sig["ndbi"]["delta"], "ndvi": sig["ndvi"]["delta"],
            "dw_built": sig["dynamic_world_built_share_pct"]["delta_pp"], "dw_bare": sig["dynamic_world_bare_share_pct"]["delta_pp"],
            "s1_vv": sig["radar_vv_db"]["delta_db"], "lst": sig["land_surface_temp_c"]["delta"],
            "viirs": sig["night_lights_radiance"]["change_pct"]}
    return {k: None if zone[k] is None or ref[k] is None else round(zone[k] - ref[k], 3) for k in REF_KEYS}


DECADE_KEYS = {"l_ndbi": 1, "l_ndvi": 1, "l_lst": 1, "s1_vv": 1, "viirs": 1, "ob_built": 100}


def decade_reference(metas, dec):
    ref = {}
    for k, scale in DECADE_KEYS.items():
        vals = {}
        for y in ("2016", "2025"):
            pts = [(dec[z][y].get(k), m["area_km2"]) for z, m in metas.items() if dec[z][y].get(k) is not None]
            vals[y] = sum(v * w for v, w in pts) / sum(w for _, w in pts)
        ref[k] = pct(vals["2016"], vals["2025"]) if k == "viirs" else round((vals["2025"] - vals["2016"]) * scale, 3)
    return ref


def decade_signals(zid, sat, dec, poi, ref):
    a, b, p = dec[zid]["2016"], dec[zid]["2025"], poi.get(zid, {})
    d = {
        "landsat_ndbi": {"2016": a.get("l_ndbi"), "2025": b.get("l_ndbi"), "delta": diff(a.get("l_ndbi"), b.get("l_ndbi"))},
        "landsat_ndvi": {"2016": a.get("l_ndvi"), "2025": b.get("l_ndvi"), "delta": diff(a.get("l_ndvi"), b.get("l_ndvi"))},
        "night_lights_radiance": {"2016": a.get("viirs"), "2025": b.get("viirs"), "change_pct": pct(a.get("viirs"), b.get("viirs"))},
        "radar_vv_db": {"2016": a.get("s1_vv"), "2025": b.get("s1_vv"), "delta_db": diff(a.get("s1_vv"), b.get("s1_vv"), 1, 2)},
        "open_buildings_cover_pct_2016_2023": {"2016": diff(0, a.get("ob_built"), 100, 2), "2023": diff(0, b.get("ob_built"), 100, 2),
                                               "delta_pp": diff(a.get("ob_built"), b.get("ob_built"), 100, 2)},
        "land_surface_temp_c": {"2016": a.get("l_lst"), "2025": b.get("l_lst"), "delta": diff(a.get("l_lst"), b.get("l_lst"), 1, 2)},
        "population_ghsl_modelled": {"2015": dec[zid].get("pop_2015"), "2025": sat.get("pop_2025"),
                                     "change_pct": pct(dec[zid].get("pop_2015"), sat.get("pop_2025"))},
        "osm_counts_2016_2026": {g: {"2016": p.get(f"osm_{g}_2016"), "2026": p.get(f"osm_{g}_2026")}
                                 for g in ("lodging", "fnb", "retail", "office")},
    }
    zone = {"l_ndbi": d["landsat_ndbi"]["delta"], "l_ndvi": d["landsat_ndvi"]["delta"], "l_lst": d["land_surface_temp_c"]["delta"],
            "s1_vv": d["radar_vv_db"]["delta_db"], "viirs": d["night_lights_radiance"]["change_pct"],
            "ob_built": d["open_buildings_cover_pct_2016_2023"]["delta_pp"]}
    rel = {k: None if zone[k] is None or ref[k] is None else round(zone[k] - ref[k], 3) for k in DECADE_KEYS}
    return d, rel


def signals_for(zid, meta, sat, poi):
    s = sat["series"]
    a, b = s[str(BASE_YEAR)], s[str(LATEST_YEAR)]
    ob0, ob1 = s.get("2019", {}), s.get("2023", {})
    p = poi.get(zid, {})
    return {
        "kecamatan": meta["name"], "area_km2": meta["area_km2"],
        "period": f"{BASE_YEAR}->{LATEST_YEAR} (satellite), Jan 2024->Oct 2026 (hotels/OSM)",
        "ndbi": {"from": a.get("ndbi"), "to": b.get("ndbi"), "delta": diff(a.get("ndbi"), b.get("ndbi"))},
        "ndvi": {"from": a.get("ndvi"), "to": b.get("ndvi"), "delta": diff(a.get("ndvi"), b.get("ndvi"))},
        "dynamic_world_built_share_pct": {"from": diff(0, a.get("dw_built"), 100, 1), "to": diff(0, b.get("dw_built"), 100, 1),
                                          "delta_pp": diff(a.get("dw_built"), b.get("dw_built"), 100, 1)},
        "dynamic_world_bare_share_pct": {"from": diff(0, a.get("dw_bare"), 100, 1), "to": diff(0, b.get("dw_bare"), 100, 1),
                                         "delta_pp": diff(a.get("dw_bare"), b.get("dw_bare"), 100, 1)},
        "dynamic_world_tree_share_delta_pp": diff(a.get("dw_trees"), b.get("dw_trees"), 100, 1),
        "night_lights_radiance": {"from": a.get("viirs"), "to": b.get("viirs"), "change_pct": pct(a.get("viirs"), b.get("viirs"))},
        "radar_vv_db": {"from": a.get("s1_vv"), "to": b.get("s1_vv"), "delta_db": diff(a.get("s1_vv"), b.get("s1_vv"), 1, 2)},
        "land_surface_temp_c": {"from": a.get("lst"), "to": b.get("lst"), "delta": diff(a.get("lst"), b.get("lst"), 1, 2)},
        "open_buildings_cover_pct_2019_2023": {"from": diff(0, ob0.get("ob_built"), 100, 2), "to": diff(0, ob1.get("ob_built"), 100, 2),
                                               "delta_pp": diff(ob0.get("ob_built"), ob1.get("ob_built"), 100, 2)},
        "open_buildings_mean_height_m": {"from": ob0.get("ob_height"), "to": ob1.get("ob_height")},
        "coastal_land_gained_km2": sat.get("land_gained_km2"),
        "population_ghsl_modelled": {"2020": sat.get("pop_2020"), "2025": sat.get("pop_2025"),
                            "change_pct": pct(sat.get("pop_2020"), sat.get("pop_2025"))},
        "built_share_2028_linear_baseline_pct": linear_2028(s, "dw_built"),
        "hotels": {k: p.get(k) for k in ("hotels_2024", "hotels_2024_still_open", "hotels_2024_closed_or_gone",
                                         "hotel_survival_pct", "hotel_type_listings_not_in_2024_scrape",
                                         "hotels_star4plus_2024", "hotel_median_price_idr_2024")},
        "osm_business_counts": {g: {"2024": p.get(f"osm_{g}_2024"), "2026": p.get(f"osm_{g}_2026"),
                                    "growth_pct": p.get(f"osm_{g}_growth_pct")} for g in ("fnb", "retail", "office")},
    }


def ask_gemma(parts, tries=6):
    for i in range(tries):
        model = GEMMA_MODEL if i % 2 == 0 else FALLBACK_MODEL
        try:
            r = client.models.generate_content(
                model=model, contents=parts,
                config=types.GenerateContentConfig(temperature=0.35, max_output_tokens=4096))
            text = r.text or ""
            m = re.search(r"\{.*\}", text, re.S)
            out = json.loads(m.group(0))
            out["_model"] = model
            return out
        except Exception as e:  # free tier: transient 503/429, occasional non-JSON
            wait = 5 * (i + 1)
            print(f"    {model} retry {i + 1}/{tries} in {wait}s: {str(e)[:100]}")
            time.sleep(wait)
    raise RuntimeError("Gemma failed")


def image_part(path):
    mime = "image/jpeg" if path.suffix == ".jpg" else "image/png"
    return types.Part.from_bytes(data=path.read_bytes(), mime_type=mime)


def main():
    metas = {m["id"]: m for m in read_json(WORK / "zones_meta.json")}
    sat, poi = read_json(WORK / "satellite_stats.json"), read_json(WORK / "poi_stats.json")
    prev = read_json(OUT / "analysis.json") if (OUT / "analysis.json").exists() else {"zones": {}}

    ref = city_reference(metas, sat)
    print("Batam-wide reference deltas:", ref)
    dec = read_json(WORK / "decade_stats.json")
    dref = decade_reference(metas, dec)
    print("Batam-wide decade deltas:", dref)

    def analyse(item):
        zid, meta = item
        sig = signals_for(zid, meta, sat[zid], poi)
        sig["batam_wide_change"] = ref
        sig["change_relative_to_batam"] = relative(sig, ref)
        sig["decade_2016_2025"], sig["decade_relative_to_batam"] = decade_signals(zid, sat[zid], dec, poi, dref)
        sig["batam_wide_decade_change"] = dref
        print(f"Gemma -> {meta['name']}")
        prompt = ANALYST_PROMPT.format(
            base=BASE_YEAR, latest=LATEST_YEAR, signals=json.dumps(sig, indent=1),
            verdicts="\n".join(f"- {k}: {v}" for k, v in VERDICTS.items()), verdict_keys=list(VERDICTS))
        imgs = [image_part(IMG / name) for name in (f"{zid}_{BASE_YEAR}.jpg", f"{zid}_{LATEST_YEAR}.jpg", f"{zid}_change.png",
                                                        f"{zid}_L2016.jpg", f"{zid}_L2025.jpg")]
        try:
            verdict = ask_gemma(imgs + [prompt])
        except RuntimeError:
            verdict = prev["zones"].get(zid, {}).get("gemma") or {"verdict": "MATURE_STABLE", "growth_score": 0,
                                                                     "headline": "Gemma unavailable", "confidence": "low"}
        print(f"   {meta['name']}: {verdict.get('verdict')} {verdict.get('growth_score')} | {verdict.get('headline')}")
        return zid, {"id": zid, "name": meta["name"], "signals": sig, "series": sat[zid]["series"], "gemma": verdict}

    with cf.ThreadPoolExecutor(4) as ex:
        zones = dict(ex.map(analyse, metas.items()))

    summary = [{"id": z["id"], "name": z["name"], "verdict": z["gemma"].get("verdict"),
                "score": z["gemma"].get("growth_score"), "headline": z["gemma"].get("headline"),
                "built_delta_pp": z["signals"]["dynamic_world_built_share_pct"]["delta_pp"],
                "night_lights_change_pct": z["signals"]["night_lights_radiance"]["change_pct"],
                "hotel_survival_pct": z["signals"]["hotels"]["hotel_survival_pct"],
                "change_relative_to_batam": z["signals"]["change_relative_to_batam"],
                "decade_relative_to_batam": z["signals"]["decade_relative_to_batam"],
                "decade_night_lights_change_pct": z["signals"]["decade_2016_2025"]["night_lights_radiance"]["change_pct"],
                "decade_story": z["gemma"].get("decade_story")} for z in zones.values()]
    print("Gemma -> city ranking")
    city = ask_gemma([RANK_PROMPT.format(zones=json.dumps(summary, indent=1), n=len(summary))])

    write_json(OUT / "analysis.json", {
        "model": " + ".join(sorted({z["gemma"].get("_model", GEMMA_MODEL) for z in zones.values()}, reverse=True)),
        "generated_at": time.strftime("%Y-%m-%d %H:%M WIB"),
        "base_year": BASE_YEAR, "latest_year": LATEST_YEAR, "models_used": dict(__import__("collections").Counter(z["gemma"].get("_model") for z in zones.values())),
        "verdicts": VERDICTS, "batam_wide_change": ref, "batam_wide_decade_change": dref,
        "city": city, "zones": zones,
    })


if __name__ == "__main__":
    main()
