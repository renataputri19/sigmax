"""Step 3: ground-truth activity signals per kecamatan.

a) Hotels: the Jan 2024 Google Maps scrape (output.xlsx) re-checked today with
   Places API (New) by place ID -> still operating / closed / gone.
b) New hotels: Places Text Search per kecamatan for lodging not in the 2024 list
   (a lower bound - Text Search returns at most 60 results per query).
c) F&B / retail / office: OpenStreetMap counts on 2024-01-06 vs today, using
   the Overpass API's historical query, so both dates come from the same source.

Places calls are hard-capped by MAX_PLACES_CALLS to stay inside the budget.
"""
import re
import time

import geopandas as gpd
import pandas as pd
import requests

from sigmax_common import OUT, PLACES_API_KEY, ROOT, WORK, read_json, write_json

MAX_PLACES_CALLS = 650
SCRAPE_DATE = "2024-01-06T00:00:00Z"
DECADE_DATE = "2016-01-01T00:00:00Z"  # start of the 2016 -> 2026 decade view
PLACES = "https://places.googleapis.com/v1"
OVERPASS = "https://overpass-api.de/api/interpreter"
HOTEL_QUERIES = ["hotel", "penginapan", "resort", "guest house"]
# primary types comparable to the 2024 scrape (star-rated hotels + melati); excludes kos/apartments
HOTEL_TYPES = {"hotel", "resort_hotel", "motel", "inn", "guest_house", "extended_stay_hotel",
               "bed_and_breakfast", "hostel"}
UA = {"User-Agent": "SIGMA-X/1.0 (Hacktoberfest Hack Day Batam 2026 project)"}

calls = 0


def places(method, path, field_mask, **kw):
    global calls
    if calls >= MAX_PLACES_CALLS:
        raise RuntimeError("Places call cap reached")
    calls += 1
    headers = {"X-Goog-Api-Key": PLACES_API_KEY, "X-Goog-FieldMask": field_mask}
    r = requests.request(method, f"{PLACES}/{path}", headers=headers, timeout=30, **kw)
    return r.status_code, r.json() if r.content else {}


def load_hotels_2024(zones):
    df = pd.read_excel(ROOT / "output.xlsx").drop(columns=["Unnamed: 0"], errors="ignore")
    df["place_id"] = df["Link"].str.extract(r"!19s([^?!&]+)")[0]
    df["stars"] = pd.to_numeric(df["Star Rating"].str.extract(r"(\d)")[0], errors="coerce")  # Melati -> NaN
    df["price_idr"] = pd.to_numeric(df["Price"].str.replace(r"[^\d]", "", regex=True), errors="coerce")
    df = df.drop_duplicates("place_id")
    g = gpd.GeoDataFrame(df, geometry=gpd.points_from_xy(df.Longitude, df.Latitude), crs=4326)
    return assign_zone(g, zones)


def assign_zone(points, zones, max_m=2000):
    """Point-in-polygon, then nearest kecamatan within max_m for coastal points just offshore."""
    utm = zones[["id", "geometry"]].to_crs(32648)
    j = gpd.sjoin_nearest(points.to_crs(32648), utm, how="left", max_distance=max_m, distance_col="dist_m")
    j = j[~j.index.duplicated()].drop(columns=["index_right"]).to_crs(4326)
    return j[j["id"].notna()]


def recheck(hotels):
    status, new_ids = [], []
    for pid in hotels["place_id"]:
        code, body = places("GET", f"places/{pid}", "id,businessStatus")
        if code == 200:
            status.append(body.get("businessStatus", "UNKNOWN"))
            new_ids.append(body.get("id", pid))  # Google refreshes place IDs over time
        elif code == 404:
            status.append("NOT_FOUND")
            new_ids.append(pid)
        else:
            status.append(f"ERROR_{code}")
            new_ids.append(pid)
            print("  places error", code, str(body)[:200])
    hotels["status_2026"] = status
    hotels["place_id_2026"] = new_ids
    return hotels


def discover_new(zones, known_ids):
    found = {}
    for _, z in zones.iterrows():
        minx, miny, maxx, maxy = z.geometry.bounds
        rect = {"rectangle": {"low": {"latitude": miny, "longitude": minx},
                              "high": {"latitude": maxy, "longitude": maxx}}}
        for q in HOTEL_QUERIES:
            token = None
            for _page in range(3):
                body = {"textQuery": f"{q} {z['name']} Batam", "includedType": "lodging",
                        "strictTypeFiltering": True, "locationRestriction": rect, "pageSize": 20}
                if token:
                    body["pageToken"] = token
                code, res = places("POST", "places:searchText",
                                   "places.id,places.displayName,places.location,places.businessStatus,"
                                   "places.primaryType,nextPageToken", json=body)
                if code != 200:
                    print("  search error", code, str(res)[:200])
                    break
                for p in res.get("places", []):
                    if p["id"] not in known_ids:
                        found[p["id"]] = {
                            "place_id": p["id"], "name": p.get("displayName", {}).get("text"),
                            "lat": p["location"]["latitude"], "lon": p["location"]["longitude"],
                            "type": p.get("primaryType"), "status": p.get("businessStatus"),
                        }
                token = res.get("nextPageToken")
                if not token:
                    break
                time.sleep(1.5)  # page tokens need a moment to become valid
        print(f"  {z['name']}: {len(found)} new-candidate lodging so far, {calls} Places calls used")
    df = pd.DataFrame(found.values())
    df.to_json(WORK / "places_new_candidates.json", orient="records", force_ascii=False)
    return filter_new(df, zones)


def filter_new(df, zones):
    g = gpd.GeoDataFrame(df, geometry=gpd.points_from_xy(df.lon, df.lat), crs=4326)
    g = assign_zone(g, zones)
    return g[(g["status"] == "OPERATIONAL") & g["type"].isin(HOTEL_TYPES)]


OSM_GROUPS = {
    "lodging": '["tourism"~"^(hotel|motel|guest_house|hostel)$"]',
    "fnb": '["amenity"~"^(restaurant|cafe|fast_food|food_court|bar)$"]',
    "retail": '["shop"]',
    "office": '["office"]',
}


def osm_counts(zones, date=None):
    minx, miny, maxx, maxy = zones.total_bounds
    bbox = f"{miny},{minx},{maxy},{maxx}"
    header = f'[out:json][timeout:120][date:"{date}"];' if date else "[out:json][timeout:120];"
    rows = []
    for group, flt in OSM_GROUPS.items():
        q = f"{header}(node{flt}({bbox});way{flt}({bbox}););out center;"
        for attempt in range(3):
            r = requests.post(OVERPASS, data={"data": q}, headers=UA, timeout=180)
            if r.status_code == 200:
                break
            time.sleep(10)
        r.raise_for_status()
        for e in r.json()["elements"]:
            lat = e.get("lat", e.get("center", {}).get("lat"))
            lon = e.get("lon", e.get("center", {}).get("lon"))
            if lat is not None:
                rows.append({"group": group, "lat": lat, "lon": lon})
    df = pd.DataFrame(rows)
    g = gpd.GeoDataFrame(df, geometry=gpd.points_from_xy(df.lon, df.lat), crs=4326)
    g = gpd.sjoin(g, zones[["id", "geometry"]], predicate="within", how="inner")
    return g.groupby(["id", "group"]).size().unstack(fill_value=0)


def main():
    zones = gpd.read_file(WORK / "zones.geojson")
    hotels = load_hotels_2024(zones)
    print(f"2024 hotels: {len(hotels)} unique, {hotels['id'].isna().sum()} outside the 12 kecamatan")

    cache = WORK / "places_recheck.json"
    if cache.exists():
        st = pd.read_json(cache).set_index("place_id")
        hotels["status_2026"] = hotels["place_id"].map(st["status_2026"])
        hotels["place_id_2026"] = hotels["place_id"].map(st["place_id_2026"])
    else:
        hotels = recheck(hotels)
        hotels[["place_id", "status_2026", "place_id_2026"]].to_json(cache, orient="records")
    print("2026 status:", hotels["status_2026"].value_counts().to_dict(), f"| Places calls: {calls}")
    if (WORK / "places_new_candidates.json").exists():
        new = filter_new(pd.read_json(WORK / "places_new_candidates.json"), zones)
    else:
        new = discover_new(zones, set(hotels["place_id"]) | set(hotels["place_id_2026"]))
    print(f"new operational lodging found: {len(new)} | Places calls: {calls}")

    print("OSM counts today / 2024 ...")
    osm_now, osm_2024, osm_2016 = osm_counts(zones), osm_counts(zones, SCRAPE_DATE), osm_counts(zones, DECADE_DATE)

    out = {}
    for _, z in zones.iterrows():
        h = hotels[hotels["id"] == z["id"]]
        n = new[new["id"] == z["id"]]
        open_2026 = int((h["status_2026"] == "OPERATIONAL").sum())
        closed = int(h["status_2026"].isin(["CLOSED_PERMANENTLY", "CLOSED_TEMPORARILY", "NOT_FOUND"]).sum())
        rec = {
            "hotels_2024": int(len(h)),
            "hotels_2024_still_open": open_2026,
            "hotels_2024_closed_or_gone": closed,
            "hotel_survival_pct": round(100 * open_2026 / len(h), 1) if len(h) else None,
            "hotel_type_listings_not_in_2024_scrape": int(len(n)),

            "hotels_star4plus_2024": int((h["stars"] >= 4).sum()),
            "hotel_median_price_idr_2024": int(h["price_idr"].median()) if h["price_idr"].notna().any() else None,
            "hotels_per_km2_2024": round(len(h) / z["area_km2"], 2),
        }
        for grp in OSM_GROUPS:
            a = int(osm_2024[grp].get(z["id"], 0)) if grp in osm_2024 else 0
            b = int(osm_now[grp].get(z["id"], 0)) if grp in osm_now else 0
            rec[f"osm_{grp}_2024"], rec[f"osm_{grp}_2026"] = a, b
            rec[f"osm_{grp}_growth_pct"] = round(100 * (b - a) / a, 1) if a else None
            c = int(osm_2016[grp].get(z["id"], 0)) if grp in osm_2016 else 0
            rec[f"osm_{grp}_2016"] = c
            rec[f"osm_{grp}_growth_2016_pct"] = round(100 * (b - c) / c, 1) if c else None
        out[z["id"]] = rec

    write_json(WORK / "poi_stats.json", out)
    points = [
        {"name": r["Name"], "lat": r["Latitude"], "lon": r["Longitude"], "zone": r["id"],
         "stars": None if pd.isna(r["stars"]) else int(r["stars"]), "status": r["status_2026"], "kind": "hotel_2024"}
        for _, r in hotels.iterrows()
    ] + [
        {"name": r["name"], "lat": r["lat"], "lon": r["lon"], "zone": r["id"], "stars": None,
         "status": "OPERATIONAL", "kind": "hotel_new"}
        for _, r in new.iterrows()
    ]
    write_json(OUT / "hotels.json", points)
    print(pd.DataFrame(out).T.to_string())
    print(f"Total Places calls: {calls}")


if __name__ == "__main__":
    main()
