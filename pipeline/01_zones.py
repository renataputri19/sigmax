"""Step 1: build the 12 Kota Batam kecamatan polygons.

Prefers the official administrative boundaries (OCHA HDX COD-AB geodatabase) and falls back
to GADM 4.1 if the HDX file has not been downloaded.
"""
import re

import geopandas as gpd

from sigmax_common import OUT, RAW, WORK, write_json

GADM_NAMES = {
    "BatamKota": "Batam Kota", "BatuAji": "Batu Aji", "BatuAmpar": "Batu Ampar",
    "Belakangpadang": "Belakang Padang", "Bengkong": "Bengkong", "Bulang": "Bulang",
    "Galang": "Galang", "LubukBaja": "Lubuk Baja", "Nongsa": "Nongsa",
    "Sagulung": "Sagulung", "SeiBeduk": "Sei Beduk", "Sekupang": "Sekupang",
}


def slug(name):
    return re.sub(r"[^a-z0-9]+", "-", name.lower()).strip("-")


def from_hdx():
    gdb = next((RAW / "hdx").glob("**/*.gdb"), None)
    if gdb is None:
        return None
    import pyogrio

    layers = [layer for layer, _ in pyogrio.list_layers(gdb)]
    layer = next(l for l in layers if re.search(r"adm(in)?_?3|adm3", l, re.I))
    g = gpd.read_file(gdb, layer=layer)
    col = lambda pat: next(c for c in g.columns if re.fullmatch(pat, c, re.I))
    adm2, adm3, pcode = col(r"adm2_(name|en)"), col(r"adm3_(name|en)"), col(r"adm3_pcode")
    g = g[g[adm2].str.contains("Batam", case=False)]
    names = g[adm3].str.title().str.replace("Sei Beduk", "Sei Beduk")
    return gpd.GeoDataFrame(
        {"name": names, "code": g[pcode], "source": "OCHA HDX COD-AB"},
        geometry=g.geometry, crs=g.crs,
    )


def from_gadm():
    g = gpd.read_file(RAW / "gadm" / "gadm41_IDN_3.json")
    g = g[g["NAME_2"].str.contains("Batam")]
    return gpd.GeoDataFrame(
        {"name": g["NAME_3"].map(GADM_NAMES), "code": g["GID_3"], "source": "GADM 4.1"},
        geometry=g.geometry, crs=g.crs,
    )


def main():
    zones = from_hdx()
    if zones is None or zones.empty:
        zones = from_gadm()
    zones = zones.to_crs(4326).dissolve(by="name", aggfunc="first").reset_index()
    zones["id"] = zones["name"].map(slug)
    zones["area_km2"] = (zones.to_crs(32648).area / 1e6).round(2)  # UTM 48N
    biggest = zones.geometry.apply(lambda g: max(getattr(g, "geoms", [g]), key=lambda p: p.area))
    zones["label_lon"] = biggest.representative_point().x.round(5)
    zones["label_lat"] = biggest.representative_point().y.round(5)
    zones = zones[["id", "name", "code", "source", "area_km2", "label_lon", "label_lat", "geometry"]].sort_values("id")
    assert len(zones) == 12, f"expected 12 kecamatan, got {len(zones)}: {list(zones['name'])}"

    zones.to_file(WORK / "zones.geojson", driver="GeoJSON")
    web = zones.copy()
    web["geometry"] = web.geometry.simplify(0.0002, preserve_topology=True)
    web.to_file(OUT / "zones.geojson", driver="GeoJSON")
    write_json(WORK / "zones_meta.json", zones.drop(columns="geometry").to_dict("records"))
    print(zones[["id", "name", "source", "area_km2"]].to_string(index=False))


if __name__ == "__main__":
    main()
