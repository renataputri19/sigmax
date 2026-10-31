# ΣX — SIGMA X
**Satellite Intelligence for Growth & Mapping Analysis — Kota Batam**

Built at **Hacktoberfest Hack Day Kota Batam × Batam Innovation Center** (31 Oct 2026).
Track: **Best Use of Gemma 4**.

SIGMA X reads satellite imagery and ground-truth activity data for the 12 kecamatan of
Batam, then uses **Gemma 4 as the analyst**: it cross-reads the numbers *and* the
before/after imagery and returns a ranked, explained verdict per kecamatan — which areas
are genuinely growing, which are only clearing land, and which are stalling — plus a 2028
outlook and a recommendation for BP Batam / Pemko Batam.

## How it works

| Step | What | Source |
|---|---|---|
| `pipeline/01_zones.py` | 12 kecamatan polygons | OCHA HDX COD-AB (official administrative boundaries) |
| `pipeline/02_satellite.py` | Per kecamatan per year 2019–2026: NDVI, NDBI, MNDWI, Dynamic World built/bare/trees, Sentinel-1 VV radar, VIIRS night lights, Landsat land-surface temperature, Open Buildings 2.5D Temporal, GHSL population, coastal reclamation; before/after and change images; city-wide map overlays | Google Earth Engine |
| `pipeline/02d_decade.py` | The decade view 2016 → 2025, from sensors that existed in both years: Landsat 8/9 NDVI, NDBI and land-surface temperature, VIIRS night lights, Sentinel-1 radar, Open Buildings 2016 → 2023, GHSL population 2015 → 2025; Landsat before/after images | Google Earth Engine |
| `pipeline/03_poi.py` | 424 hotels from a Jan 2024 Google Maps scrape re-checked by place ID (open / closed / gone), hotel listings not in the 2024 list, OSM lodging / F&B / retail / office counts for Jan 2016, Jan 2024 and today | Places API (New), OpenStreetMap Overpass (history) |
| `pipeline/04_gemma.py` | One multimodal Gemma 4 call per kecamatan (recent and decade signals JSON, each also relative to the Batam-wide change, + 5 images → verdict, score, reasoning, evidence, decade story, 2028 outlook, recommendation), then one ranking call for the city brief and decade story | Gemini API: `gemma-4-31b-it`, falling back to `gemma-4-26b-a4b-it` when 31B is overloaded |
| Laravel app | Map dashboard (MapLibre + OpenFreeMap, light/dark): choropleth by any measure, Tableau-style filters (verdict, area, score), district profiles, before/after slider, trends, full data table with CSV export, and **Ask ΣX**: live Gemma 4 Q&A grounded in the evidence | `resources/views/dashboard.blade.php`, `app/Http/Controllers/AskController.php` |

### Why Gemma, not thresholds
A plain rule says "NDBI up = growth". Gemma is given NDBI, Dynamic World *bare* share,
radar backscatter, night lights, hotel survival **and the actual imagery**, and has to
reconcile them — e.g. *"NDBI rose but the image and the bare-land share show cleared
ground, not finished structures: early stage, not realized growth."*

## Run it

```bash
# data pipeline (Python 3.12)
cd pipeline
python -m venv .venv && .venv/Scripts/pip install -r requirements.txt   # Windows
python 01_zones.py && python 02_satellite.py && python 03_poi.py && python 04_gemma.py

# web app (PHP 8.1+)
composer install
php artisan serve
```

`.env` needs `GCP_PROJECT` (registered for Earth Engine), `GEMINI_API_KEY`,
`GEMMA_MODEL=gemma-4-31b-it` and `GOOGLE_PLACES_API_KEY`.

Deploy: `gcloud run deploy sigmax --source . --region asia-southeast1` (see `Dockerfile`).

## Limits
Indicators, not official statistics. OSM counts partly reflect volunteer mapping effort, especially
in 2016 when few Batam businesses were mapped. The decade view starts in 2016 because that is the
first year with consistent Landsat 8, night-light, radar and building data over Batam (2011 has only a
few striped Landsat 5/7 scenes and no night-light, radar or building data).
"Listings not in the 2024 scrape" mixes genuinely new hotels with ones the scrape missed.
Sentinel-2 over Batam is cloud-limited; annual cloud-masked medians are used. 2019 was a dry year, so
every kecamatan is also compared with the Batam-wide change. Population is GHSL modelled data.
Individual hotel points (Google Places content) are shown only in local demos, not in the public deploy.

## AI tools used
- **Gemma 4** (`gemma-4-31b-it` and `gemma-4-26b-a4b-it`, via the Gemini API): the per-kecamatan analysis, the city ranking and the live Q&A in the product. Each verdict records which model produced it.
- **Claude Code** (Anthropic): coding assistant during the hackathon.

## Data credits
Contains modified Copernicus Sentinel data 2019–2026. Dynamic World (Google / WRI),
VIIRS DNB (NOAA EOG), Landsat (USGS), Open Buildings 2.5D Temporal (Google Research),
GHSL (EC JRC), boundaries OCHA HDX COD-AB, OpenStreetMap contributors, basemap OpenFreeMap / © OpenMapTiles.
# sigmax
