"""Step 2: satellite signals per kecamatan per year, via Google Earth Engine.

Per zone and year (2019..2026, 2026 = year to date):
  Sentinel-2 SR (Cloud Score+ masked median): NDVI, NDBI over land, MNDWI
  Dynamic World (yearly mode label): built / bare / trees / water / crops share
  VIIRS night lights: mean radiance (economic activity proxy)
  Sentinel-1 radar: median VV backscatter (sees through Batam's clouds)
  Landsat 8/9: median land surface temperature (urban heat)
  Open Buildings 2.5D Temporal (2019..2023): building cover share + mean height
Once per zone:
  GHSL population 2020 & 2025, MNDWI coastal reclamation (sea -> land since 2019)
Images:
  per-zone true-colour before/after + built-up change crops (fed to Gemma)
  city-wide overlays for the map (true colour, NDBI/NDVI change, night lights, new built-up)
"""
import calendar
import concurrent.futures as cf
import io
import json
import math
import time

import ee
import google.auth
import requests
from PIL import Image

from sigmax_common import BASE_YEAR, GCP_PROJECT, IMG, LATEST_YEAR, OUT, WORK, YEARS, read_json, write_json

creds, _ = google.auth.default(scopes=["https://www.googleapis.com/auth/earthengine",
                                       "https://www.googleapis.com/auth/cloud-platform"])
ee.Initialize(credentials=creds, project=GCP_PROJECT)

zones_gj = read_json(WORK / "zones.geojson")
ZONES = ee.FeatureCollection([
    ee.Feature(ee.Geometry(f["geometry"]), {"id": f["properties"]["id"]}) for f in zones_gj["features"]
])
LAND = ZONES.geometry()
REGION = LAND.bounds().buffer(2000).bounds()
COAST = ZONES.map(lambda f: f.buffer(1000, 50))  # 1 km seaward buffer for reclamation

CS_PLUS = ee.ImageCollection("GOOGLE/CLOUD_SCORE_PLUS/V1/S2_HARMONIZED")
DW_CLASSES = {"water": 0, "trees": 1, "crops": 4, "built": 6, "bare": 7}
OBT = ee.ImageCollection("GOOGLE/Research/open-buildings-temporal/v1")
VIIRS = ee.ImageCollection("NOAA/VIIRS/DNB/MONTHLY_V1/VCMSLCFG")


def dates(year):
    return f"{year}-01-01", f"{year + 1}-01-01"


def s2(year):
    return (ee.ImageCollection("COPERNICUS/S2_SR_HARMONIZED")
            .filterBounds(REGION).filterDate(*dates(year))
            .linkCollection(CS_PLUS, ["cs_cdf"])
            .map(lambda i: i.updateMask(i.select("cs_cdf").gte(0.6)))
            .select(["B2", "B3", "B4", "B8", "B11"]).median())


def indices(img):
    mndwi = img.normalizedDifference(["B3", "B11"]).rename("mndwi")
    land = mndwi.lt(0)
    return ee.Image.cat([
        img.normalizedDifference(["B8", "B4"]).updateMask(land).rename("ndvi"),
        img.normalizedDifference(["B11", "B8"]).updateMask(land).rename("ndbi"),
        mndwi,
    ])


def dw_label(year):
    return (ee.ImageCollection("GOOGLE/DYNAMICWORLD/V1").filterBounds(REGION)
            .filterDate(*dates(year)).select("label").mode())


def dynamic_world(year):
    label = dw_label(year)
    return ee.Image.cat([label.eq(v).rename(f"dw_{k}") for k, v in DW_CLASSES.items()])


def viirs(year):
    return VIIRS.filterDate(*dates(year)).select("avg_rad").mean().rename("viirs")


def s1(year):
    return (ee.ImageCollection("COPERNICUS/S1_GRD").filterBounds(REGION).filterDate(*dates(year))
            .filter(ee.Filter.eq("instrumentMode", "IW"))
            .filter(ee.Filter.listContains("transmitterReceiverPolarisation", "VV"))
            .select("VV").median().rename("s1_vv"))


def lst(year):
    def prep(i):
        qa = i.select("QA_PIXEL")
        clear = qa.bitwiseAnd(1 << 1).eq(0).And(qa.bitwiseAnd(1 << 3).eq(0)).And(qa.bitwiseAnd(1 << 4).eq(0))
        return i.select("ST_B10").multiply(0.00341802).add(149.0).subtract(273.15).rename("lst").updateMask(clear)
    col = (ee.ImageCollection("LANDSAT/LC08/C02/T1_L2").merge(ee.ImageCollection("LANDSAT/LC09/C02/T1_L2"))
           .filterBounds(REGION).filterDate(*dates(year)).map(prep))
    return col.median()


def obt_year(year):
    """Open Buildings tags each annual layer at 30 June 07:00 UTC, so filter by calendar year."""
    y0, y1 = calendar.timegm((year, 1, 1, 0, 0, 0)), calendar.timegm((year + 1, 1, 1, 0, 0, 0))
    return OBT.filterBounds(REGION).filter(ee.Filter.rangeContains("inference_time_epoch_s", y0, y1 - 1))


def open_buildings(year):
    m = obt_year(year).mosaic()
    built = m.select("building_presence").gt(0.5)
    return ee.Image.cat([built.rename("ob_built"),
                         m.select("building_height").updateMask(built).rename("ob_height")])


def year_stats(year, viirs_years, obt_years):
    bands = [indices(s2(year)), dynamic_world(year), s1(year), lst(year)]
    if year in viirs_years:
        bands.append(viirs(year))
    if year in obt_years:
        bands.append(open_buildings(year))
    fc = ee.Image.cat(bands).reduceRegions(collection=ZONES, reducer=ee.Reducer.mean(), scale=30, tileScale=8)
    rows = fc.getInfo()["features"]
    print(f"  {year}: done")
    return year, {r["properties"]["id"]: {k: v for k, v in r["properties"].items() if k != "id"} for r in rows}


def population():
    out = {}
    for yr in (2020, 2025):
        img = ee.Image(f"JRC/GHSL/P2023A/GHS_POP/{yr}")
        img = img.updateMask(img.gte(0))  # GHSL stores sea/no-data as -200
        fc = img.reduceRegions(collection=ZONES, reducer=ee.Reducer.sum(), scale=100).getInfo()
        for r in fc["features"]:
            out.setdefault(r["properties"]["id"], {})[f"pop_{yr}"] = round(r["properties"]["sum"])
    return out


def reclamation(year=LATEST_YEAR):
    before = indices(s2(BASE_YEAR)).select("mndwi")
    after = indices(s2(year)).select("mndwi")
    gained = before.gt(0.2).And(after.lt(-0.1))  # clearly water before, clearly land after
    lost = before.lt(-0.1).And(after.gt(0.2))
    km2 = ee.Image.cat([gained.rename("land_gained_km2"), lost.rename("land_lost_km2")]) \
        .multiply(ee.Image.pixelArea()).divide(1e6)
    try:
        fc = km2.reduceRegions(collection=COAST, reducer=ee.Reducer.sum(), scale=30, tileScale=16).getInfo()
    except ee.EEException as e:
        print("  reclamation skipped:", str(e)[:80])
        return {}
    return {r["properties"]["id"]: {k: round(r["properties"][k], 3) for k in ("land_gained_km2", "land_lost_km2")}
            for r in fc["features"]}


RGB = {"bands": ["B4", "B3", "B2"], "min": 0, "max": 2200, "gamma": 1.15}


def outline(fc, color="00E5A0", width=2):
    return ee.Image().byte().paint(fc, 1, width).visualize(palette=[color])


def built_change(y0=BASE_YEAR, y1=LATEST_YEAR):
    """Dark base, grey = built in both, green = newly built, orange = newly bare/cleared."""
    a, b = dw_label(y0), dw_label(y1)
    cls = (ee.Image(0)
           .where(a.eq(6).And(b.eq(6)), 1)
           .where(a.neq(6).And(b.eq(6)), 2)
           .where(a.neq(7).And(a.neq(6)).And(b.eq(7)), 3))
    return cls.selfMask().visualize(min=1, max=3, palette=["6B6A65", "199E70", "D95926"])


def fetch(make_url, path, tries=3):
    for i in range(tries):
        try:
            r = requests.get(make_url(), timeout=300)
            r.raise_for_status()
            path.write_bytes(r.content)
            return path.name
        except Exception as e:
            print(f"  {path.name} retry {i + 1}: {str(e)[:80]}")
            time.sleep(3)
    print(f"  FAILED {path.name}")


def merc_y(lat):
    return math.log(math.tan(math.pi / 4 + math.radians(lat) / 2))


def inv_merc_y(y):
    return math.degrees(2 * math.atan(math.exp(y)) - math.pi / 2)


def fetch_tiled(img, bbox, path, width=2048, n=2):
    """City-wide 2048px exceeds EE's per-request memory, so fetch n x n Web-Mercator tiles and stitch."""
    x0, y0, x1, y1 = bbox
    my0, my1 = merc_y(y0), merc_y(y1)
    tw = width // n
    th = round(width * (my1 - my0) / math.radians(x1 - x0)) // n
    canvas = Image.new("RGBA", (tw * n, th * n))
    for i in range(n):
        for j in range(n):
            lon_a, lon_b = x0 + (x1 - x0) * i / n, x0 + (x1 - x0) * (i + 1) / n
            lat_top, lat_bot = inv_merc_y(my1 - (my1 - my0) * j / n), inv_merc_y(my1 - (my1 - my0) * (j + 1) / n)
            params = {"region": ee.Geometry.Rectangle([lon_a, lat_bot, lon_b, lat_top]),
                      "dimensions": f"{tw}x{th}", "crs": "EPSG:3857", "format": "png"}
            for k in range(3):
                try:
                    r = requests.get(img.getThumbURL(params), timeout=300)
                    r.raise_for_status()
                    canvas.paste(Image.open(io.BytesIO(r.content)).convert("RGBA").resize((tw, th)), (i * tw, j * th))
                    break
                except Exception as e:
                    print(f"  {path.name} tile {i},{j} retry {k + 1}: {str(e)[:60]}")
                    time.sleep(3)
    canvas.save(path, optimize=True)
    print(f"  {path.name} {canvas.size}")


def zone_images():
    jobs = []
    rgb0, rgb1 = s2(BASE_YEAR).visualize(**RGB), s2(LATEST_YEAR).visualize(**RGB)
    change = ee.Image(0).visualize(palette=["1A1A19"]).blend(built_change())
    for f in zones_gj["features"]:
        zid = f["properties"]["id"]
        z = ZONES.filter(ee.Filter.eq("id", zid))
        box = z.geometry().bounds().buffer(400).bounds()
        line = outline(z)
        for tag, img in ((f"{BASE_YEAR}", rgb0), (f"{LATEST_YEAR}", rgb1), ("change", change)):
            params = {"region": box, "dimensions": 768, "format": "png"}
            jobs.append((lambda i=img.blend(line), p=params: i.getThumbURL(p), IMG / f"{zid}_{tag}.png"))
    with cf.ThreadPoolExecutor(8) as ex:
        list(ex.map(lambda j: fetch(*j), jobs))
    print(f"  {len(jobs)} zone images saved")


def overlays(only=None, n=2):
    """City-wide PNG overlays for MapLibre (EPSG:3857, exact bbox)."""
    bounds = REGION.coordinates().getInfo()[0]
    xs, ys = [p[0] for p in bounds], [p[1] for p in bounds]
    bbox = [min(xs), min(ys), max(xs), max(ys)]
    clip = LAND.buffer(300)
    d_ndbi = indices(s2(LATEST_YEAR)).select("ndbi").subtract(indices(s2(BASE_YEAR)).select("ndbi"))
    d_ndvi = indices(s2(LATEST_YEAR)).select("ndvi").subtract(indices(s2(BASE_YEAR)).select("ndvi"))
    last_viirs = VIIRS.sort("system:time_start", False).first().get("system:time_start")
    lights = VIIRS.filterDate(ee.Date(last_viirs).advance(-12, "month"), ee.Date(last_viirs).advance(1, "day")) \
        .select("avg_rad").mean()
    layers = {
        f"rgb_{BASE_YEAR}": s2(BASE_YEAR).visualize(**RGB),
        f"rgb_{LATEST_YEAR}": s2(LATEST_YEAR).visualize(**RGB),
        "d_ndbi": d_ndbi.clip(clip).visualize(min=-0.25, max=0.25, palette=["3987E5", "383835", "E66767"]),
        "d_ndvi": d_ndvi.clip(clip).visualize(min=-0.3, max=0.3, palette=["E66767", "383835", "3987E5"]),
        "night_lights": lights.clip(clip).visualize(min=0, max=40, palette=["1A1A19", "5C3D00", "C98500", "FAB219", "FFF1C2"]),
        "new_built": built_change().clip(clip),
    }
    todo = {k: v for k, v in layers.items() if only is None or k in only}
    with cf.ThreadPoolExecutor(6) as ex:
        list(ex.map(lambda kv: fetch_tiled(kv[1], bbox, IMG / f"overlay_{kv[0]}.png", n=n), todo.items()))
    last = ee.Date(last_viirs).format("YYYY-MM").getInfo()
    return {"bbox": bbox, "layers": list(layers), "night_lights_until": last}


def main():
    viirs_years = {y for y in YEARS if VIIRS.filterDate(*dates(y)).size().getInfo() > 0}
    obt_years = {y for y in YEARS if y <= 2023 and obt_year(y).size().getInfo() > 0}
    print("Open Buildings years:", sorted(obt_years))
    print("VIIRS years:", sorted(viirs_years))

    ckpt = WORK / "satellite_series.json"
    if ckpt.exists():
        series = {int(k): v for k, v in read_json(ckpt).items()}
    else:
        with cf.ThreadPoolExecutor(4) as ex:
            series = dict(ex.map(lambda y: year_stats(y, viirs_years, obt_years), YEARS))
        write_json(ckpt, series)
    pop, recl = population(), reclamation()
    print("population + reclamation done")

    stats = {}
    for f in zones_gj["features"]:
        zid = f["properties"]["id"]
        stats[zid] = {
            "series": {str(y): {k: (round(v, 4) if isinstance(v, float) else v) for k, v in series[y][zid].items()}
                       for y in YEARS},
            **pop.get(zid, {}), **recl.get(zid, {}),
        }
    write_json(WORK / "satellite_stats.json", stats)

    zone_images()
    meta = overlays()
    write_json(OUT / "overlays.json", meta)


if __name__ == "__main__":
    main()
