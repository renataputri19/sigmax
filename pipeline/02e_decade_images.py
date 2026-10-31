"""Step 2e: sharp decade before/after images, 2016 vs 2025, for the dashboard slider.

Landsat (30 m) is used for the decade numbers, but it is blurry for small kecamatan.
Sentinel-2 Level-1C (top of atmosphere, 10 m) exists for 2016 and 2025, so both sides of
the slider use the same sensor and processing. Pictures only: no indices are computed here.
Usage: python 02e_decade_images.py [zone_id ...]
"""
import concurrent.futures as cf
import importlib
import io
import sys

import ee
import requests
from PIL import Image

from sigmax_common import IMG

sat = importlib.import_module("02_satellite")  # reuses EE init, zones and helpers
TOA_RGB = {"bands": ["B4", "B3", "B2"], "min": [350, 550, 800], "max": [2300, 2200, 2300], "gamma": 1.15}


def s2_toa(year):
    return (ee.ImageCollection("COPERNICUS/S2_HARMONIZED")
            .filterBounds(sat.REGION).filterDate(*sat.dates(year))
            .linkCollection(sat.CS_PLUS, ["cs_cdf"])
            .map(lambda i: i.updateMask(i.select("cs_cdf").gte(0.6)))
            .select(["B4", "B3", "B2"]).median())


def main(only=None):
    jobs = []
    for year in (2016, 2025):
        rgb = s2_toa(year).visualize(**TOA_RGB)
        for f in sat.zones_gj["features"]:
            zid = f["properties"]["id"]
            if only and zid not in only:
                continue
            z = sat.ZONES.filter(ee.Filter.eq("id", zid))
            box = z.geometry().bounds().buffer(400).bounds()
            jobs.append((rgb.blend(sat.outline(z)), box, IMG / f"{zid}_D{year}.jpg"))

    def fetch(job):
        img, box, path = job
        for k in range(3):
            try:
                r = requests.get(img.getThumbURL({"region": box, "dimensions": 768, "format": "png"}), timeout=300)
                r.raise_for_status()
                Image.open(io.BytesIO(r.content)).convert("RGB").save(path, "JPEG", quality=84, optimize=True)
                return
            except Exception as e:
                print(f"  {path.name} retry {k + 1}: {str(e)[:80]}")
        print(f"  FAILED {path.name}")

    with cf.ThreadPoolExecutor(8) as ex:
        list(ex.map(fetch, jobs))
    print(f"  {len(jobs)} decade images saved")


if __name__ == "__main__":
    main(set(sys.argv[1:]) or None)
