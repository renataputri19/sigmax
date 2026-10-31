"""Shared paths and settings for the SIGMA X data pipeline.

The pipeline writes everything the Laravel UI needs into public/data/,
so the web app only ever reads precomputed JSON + PNG files.
"""
import json
import pathlib

from dotenv import dotenv_values

ROOT = pathlib.Path(__file__).resolve().parents[1]  # Laravel app root
PIPE = ROOT / "pipeline"
RAW = PIPE / "data" / "raw"      # downloaded source files (not committed)
WORK = PIPE / "data" / "work"    # intermediate files
OUT = ROOT / "public" / "data"   # served to the browser
IMG = OUT / "img"

for d in (WORK, OUT, IMG):
    d.mkdir(parents=True, exist_ok=True)

ENV = dotenv_values(ROOT / ".env")
GCP_PROJECT = ENV.get("GCP_PROJECT", "sigmax-batam-2026")
GEMINI_API_KEY = ENV.get("GEMINI_API_KEY")
GEMMA_MODEL = ENV.get("GEMMA_MODEL", "gemma-4-31b-it")
PLACES_API_KEY = ENV.get("GOOGLE_PLACES_API_KEY")

YEARS = list(range(2019, 2027))  # 2026 = Jan..Oct (year to date)
BASE_YEAR = 2019                 # first full year of Sentinel-2 SR over Batam
LATEST_YEAR = 2025               # latest full year, used for headline deltas


def read_json(path):
    with open(path, encoding="utf-8") as f:
        return json.load(f)


def write_json(path, obj):
    path = pathlib.Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        json.dump(obj, f, ensure_ascii=False, indent=1)
    print(f"wrote {path.relative_to(ROOT)}")
