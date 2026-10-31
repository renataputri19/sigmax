"""Step 2d: the decade view, 2016 -> 2025, using only sensors that existed in both years.

Landsat 8 (+9 in 2025, same OLI design) for NDVI / NDBI / land-surface temperature,
VIIRS night lights, Sentinel-1 radar, Open Buildings (2016 -> 2023, its latest year),
GHSL population 2015 -> 2025. Sentinel-2 SR and Dynamic World are not used here:
they were not available (or too sparse) over Batam in 2016.
Also writes per-zone Landsat true-colour images for 2016 and 2025.
"""
import concurrent.futures as cf
import importlib
import io

import ee
import requests
from PIL import Image

from sigmax_common import IMG, WORK, write_json

sat = importlib.import_module("02_satellite")  # reuses EE init, zones, and the shared layer helpers
DECADE = (2016, 2025)
L_RGB = {"bands": ["R", "G", "B"], "min": 0.0, "max": 0.22, "gamma": 1.25}


def landsat(year):
    def prep(i):
        qa = i.select("QA_PIXEL")
        clear = qa.bitwiseAnd(1 << 1).eq(0).And(qa.bitwiseAnd(1 << 3).eq(0)).And(qa.bitwiseAnd(1 << 4).eq(0))
        sr = i.select(["SR_B2", "SR_B3", "SR_B4", "SR_B5", "SR_B6"], ["B", "G", "R", "NIR", "SWIR1"]) \
            .multiply(0.0000275).add(-0.2)
        st = i.select("ST_B10").multiply(0.00341802).add(149.0).subtract(273.15).rename("lst")
        return sr.addBands(st).updateMask(clear)

    col = ee.ImageCollection("LANDSAT/LC08/C02/T1_L2")
    if year >= 2022:
        col = col.merge(ee.ImageCollection("LANDSAT/LC09/C02/T1_L2"))
    return col.filterBounds(sat.REGION).filterDate(*sat.dates(year)).map(prep).median()


def landsat_indices(img):
    land = img.normalizedDifference(["G", "SWIR1"]).lt(0)
    return ee.Image.cat([
        img.normalizedDifference(["NIR", "R"]).updateMask(land).rename("l_ndvi"),
        img.normalizedDifference(["SWIR1", "NIR"]).updateMask(land).rename("l_ndbi"),
        img.select("lst").rename("l_lst"),
    ])


def year_stats(year):
    bands = [landsat_indices(landsat(year)), sat.viirs(year), sat.s1(year), sat.open_buildings(min(year, 2023))]
    fc = ee.Image.cat(bands).reduceRegions(collection=sat.ZONES, reducer=ee.Reducer.mean(), scale=30, tileScale=8)
    rows = fc.getInfo()["features"]
    print(f"  {year}: done")
    return year, {r["properties"]["id"]: {k: (round(v, 4) if isinstance(v, float) else v)
                                          for k, v in r["properties"].items() if k != "id"} for r in rows}


def population_2015():
    img = ee.Image("JRC/GHSL/P2023A/GHS_POP/2015")
    img = img.updateMask(img.gte(0))  # GHSL stores sea/no-data as -200
    fc = img.reduceRegions(collection=sat.ZONES, reducer=ee.Reducer.sum(), scale=100).getInfo()
    return {r["properties"]["id"]: round(r["properties"]["sum"]) for r in fc["features"]}


def zone_images():
    jobs = []
    for year in DECADE:
        rgb = landsat(year).visualize(**L_RGB)
        for f in sat.zones_gj["features"]:
            zid = f["properties"]["id"]
            z = sat.ZONES.filter(ee.Filter.eq("id", zid))
            box = z.geometry().bounds().buffer(400).bounds()
            jobs.append((rgb.blend(sat.outline(z)), box, IMG / f"{zid}_L{year}.jpg"))

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
    print(f"  {len(jobs)} Landsat zone images saved")


def main():
    with cf.ThreadPoolExecutor(2) as ex:
        stats = dict(ex.map(year_stats, DECADE))
    pop15 = population_2015()
    out = {zid: {str(y): stats[y][zid] for y in DECADE} | {"pop_2015": pop15.get(zid)}
           for zid in stats[DECADE[0]]}
    write_json(WORK / "decade_stats.json", out)
    zone_images()


if __name__ == "__main__":
    main()
