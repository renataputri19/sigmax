@verbatim
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ΣX · SIGMA X — Batam growth intelligence</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" rel="stylesheet">
<script>
// set the theme before first paint: saved choice, else the OS preference (dark when it has none)
(function () {
  var t; try { t = localStorage.getItem('sigmax-theme'); } catch (e) {}
  document.documentElement.dataset.theme = t === 'light' || t === 'dark' ? t : (matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
})();
</script>
<style>
:root {
  color-scheme: dark;
  --page: #0d0d0d; --surface-1: #1a1a19; --surface-2: #232321; --border: rgba(255,255,255,0.10); --border-strong: rgba(255,255,255,0.25);
  --text-primary: #f0efec; --text-secondary: #c3c2b7; --text-muted: #898781;
  --grid: #2c2c2a; --baseline: #383835;
  --accent: #00E5A0; --accent-strong: #00E5A0; --on-accent: #0d0d0d;
  --shadow: rgba(0,0,0,.5); --backdrop: rgba(0,0,0,.6);
  /* categorical verdict slots (dark steps, fixed order) */
  --v-growth: #199e70; --v-early: #c98500; --v-under: #3987e5; --v-stalled: #d95926; --v-mature: #9085e9; --v-decline: #e66767;
  --critical: #d03b3b;
  /* map fills drawn live (the overlay PNGs are pre-rendered and keep their own colours) */
  --seq-1: #12352b; --seq-2: #145c47; --seq-3: #199e70; --seq-4: #3fd49d; --seq-5: #a6f5d6;
  --div-neg: #e66767; --div-mid: #383835; --div-pos: #3987e5; --no-data: #2c2c2a;
  --zone-gap: #0d0d0d; --zone-line-overlay: rgba(240,239,236,0.55); --label-halo: #0d0d0d;
  --hotel-open: #c3c2b7; --hotel-new: #3987e5; --hotel-ring: #0d0d0d;
}
:root[data-theme="light"] {
  color-scheme: light;
  --page: #f9f9f7; --surface-1: #fcfcfb; --surface-2: #f3f2ee; --border: rgba(11,11,11,0.10); --border-strong: rgba(11,11,11,0.25);
  --text-primary: #0b0b0b; --text-secondary: #52514e; --text-muted: #6b6a65;
  --grid: #e1e0d9; --baseline: #c3c2b7;
  --accent: #00a37a; --accent-strong: #007a55; --on-accent: #ffffff;
  --shadow: rgba(11,11,11,.14); --backdrop: rgba(11,11,11,.35);
  /* categorical verdict slots (light steps, same order) */
  --v-growth: #1baf7a; --v-early: #eda100; --v-under: #2a78d6; --v-stalled: #eb6834; --v-mature: #6250d6; --v-decline: #e34948;
  --seq-1: #d3f3e5; --seq-2: #86d9b6; --seq-3: #1baf7a; --seq-4: #11805a; --seq-5: #0b4f38;
  --div-neg: #e34948; --div-mid: #f0efec; --div-pos: #2a78d6; --no-data: #e1e0d9;
  --zone-gap: #ffffff; --zone-line-overlay: rgba(137,135,129,0.9); --label-halo: #fcfcfb;
  --hotel-open: #898781; --hotel-new: #2a78d6; --hotel-ring: #ffffff;
}
* { box-sizing: border-box; }
html, body { margin: 0; height: 100%; overflow: hidden; background: var(--page); color: var(--text-primary); font: 14px/1.5 Inter, system-ui, sans-serif; }
body { display: flex; flex-direction: column; height: 100dvh; }  /* dvh: the visible height on phones, not the height behind the URL bar */
h1, h2, h3, .display { font-family: "Space Grotesk", Inter, sans-serif; letter-spacing: -0.01em; margin: 0; }
button, input { font: inherit; color: inherit; }

/* ---------- header ---------- */
header { flex: none; height: 56px; display: flex; align-items: center; gap: 16px; padding: 0 20px; border-bottom: 1px solid var(--border); background: var(--surface-1); }
.wordmark { font-family: "Space Grotesk"; font-weight: 700; font-size: 26px; letter-spacing: -0.04em; line-height: 1; }
.wordmark span { color: var(--accent-strong); }
.tagline { color: var(--text-secondary); font-size: 13px; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
header > :not(.tagline) { flex-shrink: 0; }
.tagline b { color: var(--text-primary); font-weight: 600; }
header .spacer { flex: 1; }
.chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border: 1px solid var(--border); border-radius: 999px; font-size: 12px; color: var(--text-secondary); background: var(--surface-2); }
.chip .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); box-shadow: 0 0 8px var(--accent); }
.link-btn { background: none; border: 1px solid var(--border); border-radius: 8px; padding: 5px 12px; cursor: pointer; color: var(--text-secondary); }
.link-btn:hover { color: var(--text-primary); border-color: var(--border-strong); }
.theme-btn { min-width: 74px; }

/* ---------- layout ---------- */
main { flex: 1; min-height: 0; display: grid; grid-template-columns: 340px minmax(0, 1fr) 430px; grid-template-rows: minmax(0, 1fr); }
aside { overflow-y: auto; background: var(--surface-1); }
aside.left { border-right: 1px solid var(--border); padding: 18px; }
aside.right { border-left: 1px solid var(--border); padding: 0; display: flex; flex-direction: column; overflow: hidden; }
.eyebrow { font-size: 11px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; }
.card { background: var(--surface-2); border: 1px solid var(--border); border-radius: 12px; padding: 14px; }
.muted { color: var(--text-muted); } .secondary { color: var(--text-secondary); }

/* city brief */
.city-headline { font-size: 19px; line-height: 1.25; font-weight: 700; margin-bottom: 8px; }
.city-brief { color: var(--text-secondary); font-size: 13px; margin: 0 0 14px; }
.callouts { display: grid; gap: 8px; margin-bottom: 18px; }
.callout { border-left: 3px solid var(--accent); padding: 6px 10px; background: var(--surface-2); border-radius: 0 8px 8px 0; font-size: 12.5px; color: var(--text-secondary); }
.callout.decade { border-left-color: var(--v-under); }
.why-t { width: 100%; border-collapse: collapse; font-size: 12px; }
.why-t td { padding: 5px 0; border-bottom: 1px solid var(--grid); color: var(--text-secondary); }
.why-t tr:last-child td { border-bottom: 0; }
.why-t td.v { text-align: right; color: var(--text-primary); font-weight: 600; white-space: nowrap; padding-left: 8px; }
.why-t td.c { text-align: right; color: var(--text-muted); white-space: nowrap; padding-left: 8px; width: 1%; }
.info-btn { background: none; border: 0; padding: 0 0 0 4px; color: var(--accent-strong); cursor: pointer; font-size: 11px; }
.info-btn:hover { text-decoration: underline; }
#score-help { max-width: min(640px, calc(100vw - 24px)); }
#score-help .vlist { display: grid; gap: 6px; margin: 8px 0 12px; }
#score-help .vlist div { display: flex; gap: 8px; align-items: baseline; font-size: 12.5px; color: var(--text-secondary); }
.cmp-seg { margin-bottom: 8px; }
.cmp-seg button { font-size: 11px; }
.callout.risk { border-left-color: var(--critical); }
.callout b { color: var(--text-primary); }

/* ranking */
.rank-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 4px; }
.rank-item { display: grid; grid-template-columns: 22px 1fr 34px; align-items: center; gap: 8px; padding: 8px; border-radius: 8px; cursor: pointer; border: 1px solid transparent; }
.rank-item:hover { background: var(--surface-2); }
.rank-item.active { background: var(--surface-2); border-color: color-mix(in srgb, var(--accent) 45%, transparent); }
.rank-no { font-family: "Space Grotesk"; color: var(--text-muted); font-weight: 700; text-align: right; }
.rank-name { font-weight: 600; font-size: 13.5px; }
.rank-sub { display: flex; align-items: center; gap: 6px; margin-top: 3px; }
.bar { height: 6px; background: var(--grid); border-radius: 3px; flex: 1; overflow: hidden; }
.bar > i { display: block; height: 100%; border-radius: 0 3px 3px 0; background: var(--accent); }
.rank-score { font-family: "Space Grotesk"; font-weight: 700; text-align: right; }
.filters { background: var(--surface-2); border: 1px solid var(--border); border-radius: 12px; padding: 12px; margin-bottom: 16px; }
.filters .eyebrow { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0; }
.reset { background: none; border: 0; color: var(--accent-strong); font-size: 11px; cursor: pointer; padding: 0; letter-spacing: 0; text-transform: none; }
.fgroup { margin-top: 10px; }
.flabel { font-size: 11.5px; color: var(--text-muted); margin-bottom: 5px; display: flex; justify-content: space-between; }
.flabel b { color: var(--text-primary); }
.fchips { display: flex; flex-wrap: wrap; gap: 5px; }
.fchips .badge { cursor: pointer; user-select: none; }
.fchips .badge.off { opacity: .35; background: transparent; }
.seg { display: flex; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
.seg button { flex: 1; background: none; border: 0; padding: 5px 4px; font-size: 11.5px; color: var(--text-secondary); cursor: pointer; }
.seg button.on { background: var(--accent-strong); color: var(--on-accent); font-weight: 600; }
.filters input[type=range] { width: 100%; accent-color: var(--accent-strong); margin: 0; }
.legend select { width: 100%; background: var(--surface-2); color: var(--text-primary); border: 1px solid var(--border); border-radius: 6px; padding: 5px 6px; margin-bottom: 6px; font-size: 12.5px; }
.profile { width: 100%; border-collapse: collapse; font-size: 12px; }
.profile td { padding: 5px 0; border-bottom: 1px solid var(--grid); color: var(--text-secondary); vertical-align: top; }
.profile tr:last-child td { border-bottom: 0; }
.profile td:last-child { text-align: right; color: var(--text-primary); font-weight: 500; padding-left: 10px; }

/* verdict badge: colour + icon + label, never colour alone */
.badge { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 999px; background: color-mix(in srgb, var(--c) 18%, transparent); color: var(--text-primary); border: 1px solid color-mix(in srgb, var(--c) 60%, transparent); white-space: nowrap; }
.badge::before { content: attr(data-icon); color: var(--c); font-size: 10px; }

/* ---------- map ---------- */
.map-wrap { position: relative; }
#map { position: absolute; inset: 0; }
.map-top { position: absolute; top: 14px; left: 14px; right: 56px; z-index: 2; display: flex; flex-direction: column; align-items: flex-start; gap: 8px; pointer-events: none; }
.map-top > * { pointer-events: auto; }
.layer-ctl { display: flex; flex-wrap: wrap; gap: 4px; background: color-mix(in srgb, var(--surface-1) 88%, transparent); backdrop-filter: blur(8px); border: 1px solid var(--border); border-radius: 10px; padding: 4px; }
.layer-ctl button { background: none; border: 0; border-radius: 7px; padding: 6px 10px; cursor: pointer; color: var(--text-secondary); font-size: 12.5px; font-weight: 500; }
.layer-ctl button:hover { color: var(--text-primary); background: var(--surface-2); }
.layer-ctl button.on { color: var(--on-accent); background: var(--accent-strong); }
.layer-ctl label { display: flex; align-items: center; gap: 6px; padding: 6px 10px; font-size: 12.5px; color: var(--text-secondary); cursor: pointer; border-left: 1px solid var(--border); }
.legend { width: min(300px, 100%); background: color-mix(in srgb, var(--surface-1) 90%, transparent); backdrop-filter: blur(8px); border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; font-size: 12px; color: var(--text-secondary); }
.legend .title { color: var(--text-primary); font-weight: 600; margin-bottom: 6px; }
.ramp { height: 10px; border-radius: 3px; margin: 4px 0 3px; }
.ramp-labels { display: flex; justify-content: space-between; color: var(--text-muted); font-size: 11px; }
.swatches { display: grid; gap: 4px; }
.swatches div { display: flex; align-items: center; gap: 8px; }
.swatches i { width: 12px; height: 12px; border-radius: 3px; display: inline-block; }
.fade { display: flex; align-items: center; gap: 8px; margin-top: 6px; }
.fade input { flex: 1; accent-color: var(--accent-strong); }
.map-tip { position: absolute; z-index: 3; pointer-events: none; background: var(--surface-1); border: 1px solid var(--border); border-radius: 8px; padding: 8px 10px; font-size: 12px; box-shadow: 0 6px 24px var(--shadow); display: none; min-width: 170px; }
.map-tip b { font-size: 13px; }
.data-table { border-collapse: collapse; font-size: 12px; white-space: nowrap; }
.data-table th, .data-table td { padding: 6px 8px; border-bottom: 1px solid var(--grid); text-align: right; }
.data-table th { color: var(--text-muted); font-weight: 600; position: sticky; top: 0; background: var(--surface-1); text-align: right; }
.data-table th:first-child, .data-table td:first-child { text-align: left; position: sticky; left: 0; background: var(--surface-1); color: var(--text-primary); font-weight: 600; }
.data-table tr:hover td { background: var(--surface-2); }
.data-table th.grp { text-align: center; color: var(--text-secondary); border-bottom: 1px solid var(--baseline); }
#data { max-width: min(1400px, 96vw); width: 96vw; }
.table-wrap { overflow: auto; max-height: 70vh; border: 1px solid var(--border); border-radius: 10px; }
.maplibregl-ctrl-attrib { background: color-mix(in srgb, var(--page) 70%, transparent) !important; color: var(--text-muted) !important; font-size: 10px; }
.maplibregl-ctrl-attrib a { color: var(--text-secondary) !important; }

/* ask box */
/* right panel: District | Ask ΣX tabs */
.tabs { flex: none; display: flex; gap: 2px; padding: 8px 14px 0; border-bottom: 1px solid var(--border); }
.tabs button, .view-nav button { background: none; border: 0; border-bottom: 2px solid transparent; padding: 8px 10px 9px; margin-bottom: -1px; cursor: pointer; color: var(--text-secondary); font-weight: 600; font-size: 13px; }
.tabs button:hover, .view-nav button:hover { color: var(--text-primary); }
.tabs button.on, .view-nav button.on { color: var(--text-primary); border-bottom-color: var(--accent-strong); }
.tabs .sub { color: var(--text-muted); font-weight: 500; }
.pane { flex: 1; min-height: 0; }
.pane[hidden] { display: none !important; }
#detail { overflow-y: auto; padding: 18px 20px 40px; container-type: inline-size; }
#ask-pane { display: flex; flex-direction: column; }
.chat-log { flex: 1; min-height: 0; overflow-y: auto; padding: 16px 18px; display: flex; flex-direction: column; gap: 12px; }
.chat-intro { color: var(--text-secondary); font-size: 13px; }
.chat-intro h3 { font-size: 17px; color: var(--text-primary); margin-bottom: 4px; }
.suggest { display: flex; flex-direction: column; gap: 6px; margin-top: 12px; }
.suggest button { text-align: left; background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; padding: 7px 10px; font-size: 12.5px; color: var(--text-secondary); cursor: pointer; }
.suggest button:hover { color: var(--text-primary); border-color: var(--border-strong); }
.msg { border-radius: 12px; padding: 10px 12px; font-size: 13px; line-height: 1.55; }
.msg.q { align-self: flex-end; max-width: 88%; background: color-mix(in srgb, var(--accent) 14%, var(--surface-2)); color: var(--text-primary); border: 1px solid color-mix(in srgb, var(--accent) 35%, transparent); }
.msg.a { background: var(--surface-2); border: 1px solid var(--border); color: var(--text-secondary); }
.msg.a .who { font-size: 11px; color: var(--accent-strong); font-weight: 600; margin-bottom: 6px; }
.md p { margin: 0 0 8px; } .md p:last-child { margin-bottom: 0; }
.md ol, .md ul { margin: 0 0 8px; padding-left: 20px; } .md li { margin-bottom: 5px; } .md li:last-child { margin-bottom: 0; }
.md b { color: var(--text-primary); font-weight: 600; }
.md h4 { margin: 8px 0 4px; font-size: 13px; color: var(--text-primary); }
.md code { font-size: 12px; background: var(--grid); padding: 0 4px; border-radius: 4px; }
.thinking::after { content: '…'; display: inline-block; width: 1em; overflow: hidden; vertical-align: bottom; animation: dots 1.2s steps(4) infinite; }
@keyframes dots { from { width: 0; } to { width: 1em; } }
@media (prefers-reduced-motion: reduce) { .thinking::after { animation: none; } }
.chat-form { flex: none; display: flex; gap: 8px; padding: 12px 14px; border-top: 1px solid var(--border); background: var(--surface-1); }
.chat-form input { flex: 1; min-width: 0; background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; outline: none; padding: 9px 11px; }
.chat-form input:focus { border-color: color-mix(in srgb, var(--accent) 60%, transparent); }
.chat-form button { background: var(--accent-strong); color: var(--on-accent); border: 0; border-radius: 10px; padding: 8px 16px; font-weight: 600; cursor: pointer; }
.chat-form button:disabled { opacity: .5; cursor: wait; }

/* ---------- zone detail ---------- */
.zone-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 10px; }
.zone-name { font-size: 26px; font-weight: 700; line-height: 1.1; }
.score-big { text-align: right; }
.score-big .n { font: 700 40px/1 "Space Grotesk"; color: var(--accent-strong); }
.score-big .l { font-size: 11px; color: var(--text-muted); }
.headline { font-size: 15px; font-weight: 600; margin: 8px 0 14px; line-height: 1.35; }
.compare { position: relative; aspect-ratio: 1 / 1; border-radius: 12px; overflow: hidden; border: 1px solid var(--border); background: #000; user-select: none; }
.compare img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.compare .after { clip-path: inset(0 0 0 50%); }
.compare .handle { position: absolute; top: 0; bottom: 0; width: 2px; background: var(--accent); left: 50%; pointer-events: none; box-shadow: 0 0 10px var(--accent); }
.compare .tag { position: absolute; top: 8px; font: 700 12px "Space Grotesk"; background: rgba(13,13,13,.75); color: #f0efec; padding: 2px 8px; border-radius: 6px; }
.compare .tag.l { left: 8px; } .compare .tag.r { right: 8px; }
.compare input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: ew-resize; margin: 0; touch-action: pan-y; }
.img-row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px; }
.img-row figure { margin: 0; }
.img-row img { width: 100%; border-radius: 8px; border: 1px solid var(--border); display: block; }
.img-row figcaption { font-size: 11px; color: var(--text-muted); margin-top: 3px; }
.section { margin-top: 18px; }
.tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); gap: 8px; }
.tile { background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; padding: 9px 10px; }
.tile .k { font-size: 11px; color: var(--text-muted); line-height: 1.25; min-height: 28px; }
.tile .v { font: 700 18px/1.2 "Space Grotesk"; margin-top: 2px; }
.tile .d { font-size: 11px; color: var(--text-secondary); }
.sparks { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.spark { background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; padding: 8px 10px; position: relative; }
.spark .k { font-size: 11px; color: var(--text-muted); display: flex; justify-content: space-between; }
.spark .k b { color: var(--text-primary); font-weight: 600; }
.spark svg { display: block; width: 100%; height: 44px; margin-top: 4px; overflow: visible; }
.spark svg .base { stroke: var(--baseline); }
.spark svg .line { stroke: var(--accent); }
.spark svg circle { fill: var(--accent); stroke: var(--surface-2); }
.prose { color: var(--text-secondary); font-size: 13px; margin: 6px 0 0; }
.evidence { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 6px; }
.evidence td { padding: 6px 4px; border-top: 1px solid var(--grid); vertical-align: top; color: var(--text-secondary); }
.evidence td:first-child { color: var(--text-primary); font-weight: 600; width: 32%; }
.outlook { display: flex; gap: 12px; align-items: center; }
.outlook .n { font: 700 28px/1 "Space Grotesk"; }
.reco { border-left: 3px solid var(--accent); }
.empty { color: var(--text-muted); padding: 40px 10px; text-align: center; }

/* modal */
dialog { background: var(--surface-1); color: var(--text-primary); border: 1px solid var(--border); border-radius: 14px; max-width: min(640px, calc(100vw - 24px)); padding: 22px 24px; }
dialog::backdrop { background: var(--backdrop); }
dialog li { color: var(--text-secondary); margin-bottom: 6px; font-size: 13px; }

/* ---------- smaller screens ---------- */
/* only used below 1100px: the view switcher, the legend toggle and the phone map card */
.view-nav, .legend-toggle, .map-peek { display: none; }
.legend-toggle { align-items: center; gap: 6px; background: color-mix(in srgb, var(--surface-1) 88%, transparent); backdrop-filter: blur(8px); border: 1px solid var(--border); border-radius: 999px; padding: 5px 12px; font-size: 12px; font-weight: 600; color: var(--text-secondary); cursor: pointer; }
.legend-toggle::after { content: '▾'; font-size: 10px; }
.legend-open .legend-toggle::after { content: '▴'; }
.map-peek { position: absolute; left: 10px; right: 10px; bottom: 10px; z-index: 3; align-items: center; gap: 12px; padding: 10px 12px; text-align: left; cursor: pointer; background: var(--surface-1); border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 6px 24px var(--shadow); }
.map-peek .pk-main { flex: 1; min-width: 0; display: flex; flex-direction: column; align-items: flex-start; gap: 3px; }
.map-peek .pk-name { font: 700 16px/1.2 "Space Grotesk"; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.map-peek .pk-score { font: 700 26px/1 "Space Grotesk"; color: var(--accent-strong); }
.map-peek .pk-go { font-size: 12px; font-weight: 600; color: var(--accent-strong); white-space: nowrap; }

@container (max-width: 320px) {
  .img-row { grid-template-columns: 1fr; }
  .zone-name { font-size: 22px; }
  .score-big .n { font-size: 34px; }
}

@media (max-width: 1280px) { main { grid-template-columns: 290px minmax(0, 1fr) 370px; } }

/* tablet: map beside one panel, picked with the tabs above it */
@media (max-width: 1100px) {
  main { grid-template-columns: minmax(0, 1fr) clamp(300px, 42vw, 420px); grid-template-rows: auto minmax(0, 1fr); grid-template-areas: "map nav" "map side"; }
  .map-wrap { grid-area: map; }
  aside.left, aside.right { grid-area: side; border-left: 1px solid var(--border); border-right: 0; }
  main[data-view="detail"] aside.left, main[data-view="ask"] aside.left,
  main[data-view="map"] aside.right, main[data-view="brief"] aside.right { display: none; }
  aside.right .tabs { display: none; }
  .view-nav { grid-area: nav; display: flex; gap: 2px; padding: 8px 14px 0; background: var(--surface-1); border-left: 1px solid var(--border); border-bottom: 1px solid var(--border); }
  .view-nav [data-view="map"], .view-nav svg { display: none; }
}
@media (max-width: 900px) {
  .tagline { display: none; }
  header .chip { min-width: 0; flex-shrink: 1; }  /* the model name truncates before the buttons are pushed off */
  .chip .dot { flex: none; }
  #model-chip { min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
}

/* phone: one view at a time, switched from a bottom tab bar */
@media (max-width: 720px) {
  header { gap: 6px; padding: 0 12px; }
  .tagline, .chip { display: none; }
  .link-btn { padding: 5px 9px; }
  .theme-btn { min-width: 0; }
  .theme-btn .lbl { display: none; }
  main { grid-template-columns: minmax(0, 1fr); grid-template-rows: minmax(0, 1fr) auto; grid-template-areas: "view" "nav"; }
  .map-wrap, aside.left, aside.right { grid-area: view; border: 0; }
  main[data-view="map"] aside.left { display: none; }
  /* hidden, not removed: the map keeps its size, so it needs no resize when shown again. MapLibre forces some
     controls visible, so the map also sits in its own layer under the panels. */
  main:not([data-view="map"]) .map-wrap { visibility: hidden; }
  .map-wrap { z-index: 0; }
  aside.left, aside.right { position: relative; z-index: 1; }
  .view-nav { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; padding: 0; border: 0; border-top: 1px solid var(--border); }
  .view-nav button, .view-nav [data-view="map"] { display: flex; flex-direction: column; align-items: center; gap: 2px; margin: 0; padding: 7px 2px 8px; border: 0; font-size: 11px; font-weight: 500; }
  .view-nav svg { display: block; width: 22px; height: 22px; }
  .view-nav button.on { color: var(--accent-strong); font-weight: 600; }
  .map-top { top: 10px; left: 10px; right: 50px; }
  .layer-ctl { flex-wrap: nowrap; max-width: 100%; overflow-x: auto; scrollbar-width: none; }
  .layer-ctl::-webkit-scrollbar { display: none; }
  .layer-ctl button, .layer-ctl label { flex: none; white-space: nowrap; }
  .map-peek:not(:empty) { display: flex; }
  .map-wrap .maplibregl-ctrl-bottom-left, .map-wrap .maplibregl-ctrl-bottom-right { bottom: 74px; }  /* sit above the map card */
  aside.left { padding: 16px; }
  #detail { padding: 16px 16px 32px; }
  .chat-log { padding: 14px; }
  .chat-form { padding: 10px 12px; }
  .chat-form input, .legend select { font-size: 16px; }  /* below 16px iOS zooms the page on focus */
  dialog { padding: 18px 16px; }
}

/* small or short map: legend folds away behind a toggle (starts open on tablets, closed on phones) */
@media (max-width: 1100px), (max-height: 560px) {
  .legend-toggle { display: inline-flex; }
  .map-top:not(.legend-open) .legend { display: none; }
}
</style>
</head>
<body>
<header>
  <div class="wordmark">Σ<span>X</span></div>
  <div class="tagline"><b>SIGMA X</b> · Satellite Intelligence for Growth &amp; Mapping Analysis · Kota Batam</div>
  <div class="spacer"></div>
  <span class="chip"><span class="dot"></span><span id="model-chip">Reasoned by Gemma 4</span></span>
  <button class="link-btn" onclick="openData()">All data</button>
  <button class="link-btn" onclick="document.getElementById('about').showModal()">Method</button>
  <button class="link-btn theme-btn" id="theme-toggle" type="button"></button>
</header>

<main id="main" data-view="map">
  <aside class="left">
    <div class="eyebrow">Gemma 4 city brief</div>
    <h2 class="city-headline" id="city-headline">Loading analysis…</h2>
    <p class="city-brief" id="city-brief"></p>
    <div class="callouts" id="callouts"></div>
    <div class="filters">
      <div class="eyebrow">Filters <button class="reset" id="f-reset" type="button">Reset</button></div>
      <div class="fgroup"><div class="flabel">Gemma verdict (click to toggle)</div><div class="fchips" id="f-verdict"></div></div>
      <div class="fgroup"><div class="flabel">Area</div>
        <div class="seg" id="f-area"><button type="button" data-v="all" class="on">All</button><button type="button" data-v="mainland">Mainland</button><button type="button" data-v="islands">Hinterland islands</button></div></div>
      <div class="fgroup"><div class="flabel">Minimum growth score <b id="f-score-v">0</b></div><input type="range" id="f-score" min="0" max="100" step="5" value="0" aria-label="Minimum growth score"></div>
    </div>
    <div class="eyebrow">Growth ranking · <span id="rank-count">12</span> of 12 kecamatan</div>
    <ol class="rank-list" id="ranking"></ol>
  </aside>

  <section class="map-wrap">
    <div id="map"></div>
    <div class="map-top" id="map-top">
    <div class="layer-ctl" id="layer-ctl">
      <button data-layer="score" class="on">Kecamatan data</button>
      <button data-layer="rgb">True colour</button>
      <button data-layer="new_built">New construction</button>
      <button data-layer="d_ndbi">Δ Built-up</button>
      <button data-layer="d_ndvi">Δ Vegetation</button>
      <button data-layer="night_lights">Night lights</button>
      <label><input type="checkbox" id="hotels-toggle"> Hotels</label>
    </div>
    <button class="legend-toggle" id="legend-toggle" type="button" aria-expanded="false" aria-controls="legend">Legend</button>
    <div class="legend" id="legend"></div>
    </div>
    <div class="map-tip" id="map-tip"></div>
    <button class="map-peek" id="map-peek" type="button"></button>
  </section>

  <aside class="right">
    <div class="tabs" role="tablist">
      <button type="button" role="tab" data-tab="detail" class="on" aria-selected="true">District</button>
      <button type="button" role="tab" data-tab="ask-pane" aria-selected="false">Ask ΣX <span class="sub">· Gemma 4</span></button>
    </div>
    <div class="pane" id="detail" role="tabpanel"><div class="empty">Select a kecamatan on the map or in the ranking.</div></div>
    <div class="pane" id="ask-pane" role="tabpanel" hidden>
      <div class="chat-log" id="chat-log" aria-live="polite">
        <div class="chat-intro"><h3>Ask ΣX</h3>Gemma 4 answers from the same evidence behind the map: satellite signals 2019→2025, hotels and OSM 2024→2026, and its own verdicts. English or Bahasa Indonesia.
          <div class="suggest" id="suggest"></div></div>
      </div>
      <form id="ask-form" class="chat-form">
        <input id="ask-input" placeholder="Ask about Batam's growth…" autocomplete="off" maxlength="500" aria-label="Question for Gemma">
        <button id="ask-btn" type="submit">Ask</button>
      </form>
    </div>
  </aside>

  <nav class="view-nav" id="view-nav" aria-label="Views">
    <button type="button" data-view="map"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2Z"/><path d="M9 4v14M15 6v14"/></svg><span>Map</span></button>
    <button type="button" data-view="brief"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 20h16M7 16v-5M12 16V6M17 16v-8"/></svg><span>Overview</span></button>
    <button type="button" data-view="detail"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/></svg><span>District</span></button>
    <button type="button" data-view="ask"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H9l-5 4V5Z"/></svg><span>Ask ΣX</span></button>
  </nav>
</main>

<dialog id="data">
  <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;margin-bottom:10px;gap:12px">
    <h2 style="font-size:20px">All signals · 12 kecamatan</h2>
    <div><a class="link-btn" href="/data/analysis.json" download style="text-decoration:none">Download JSON</a> <button class="link-btn" onclick="downloadCSV()">Download CSV</button> <button class="link-btn" onclick="document.getElementById('data').close()">Close</button></div>
  </div>
  <div class="table-wrap"><table class="data-table" id="data-table"></table></div>
  <p class="muted" style="font-size:11.5px;margin-bottom:0">Δ = change from the base year to the latest full year (satellite), Jan 2024 → Oct 2026 (hotels, OSM). pp = percentage points. Click a row to open that kecamatan.</p>
</dialog>

<dialog id="score-help">
  <h2 style="font-size:20px;margin-bottom:8px">How the growth score is made</h2>
  <div id="score-help-body" class="prose"></div>
  <form method="dialog" style="text-align:right;margin-top:12px"><button class="link-btn">Close</button></form>
</dialog>

<dialog id="about">
  <h2 style="font-size:20px;margin-bottom:10px">How ΣX works</h2>
  <ol>
    <li><b>Zones:</b> the 12 kecamatan of Kota Batam, official administrative boundaries (OCHA HDX COD-AB).</li>
    <li><b>Satellite (Google Earth Engine), 2019→2025 + 2026 YTD:</b> Sentinel-2 NDVI / NDBI / MNDWI (Cloud Score+ masked medians), Dynamic World land cover, Sentinel-1 radar backscatter, VIIRS night lights, Landsat 8/9 land-surface temperature, Google Open Buildings 2.5D Temporal (2019–2023), GHSL population.</li>
    <li><b>Ground truth:</b> 424 hotels from a Jan 2024 Google Maps scrape, re-checked one by one with the Places API in Oct 2026 (still open / closed / gone), plus hotel-type listings not in the 2024 list; OpenStreetMap F&amp;B, retail and office counts Jan 2024 vs today.</li>
    <li><b>Gemma 4 (gemma-4-31b-it) is the analyst:</b> per kecamatan it reads the numbers <i>and</i> the before/after imagery plus a change map, reconciles conflicts (e.g. NDBI up but the land is only cleared), and returns a verdict, score, 2028 outlook and recommendation. A second call ranks all zones. “Ask ΣX” queries Gemma live over the same evidence.</li>
  </ol>
  <p class="muted" style="font-size:12px">Indicators, not official statistics. OSM counts partly reflect mapping effort; new-listing counts mix new hotels with ones the 2024 scrape missed. Contains modified Copernicus Sentinel data 2019–2026. Basemap: OpenFreeMap, © OpenMapTiles, © OpenStreetMap contributors.</p>
  <form method="dialog" style="text-align:right"><button class="link-btn">Close</button></form>
</dialog>

<script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>
<script>
const VERDICT = {
  GROWTH_CORRIDOR:   { label: 'Growth corridor',       icon: '▲', c: 'var(--v-growth)' },
  EARLY_STAGE:       { label: 'Early stage',           icon: '◐', c: 'var(--v-early)' },
  UNDERBUILT_DEMAND: { label: 'Underbuilt, in demand', icon: '◆', c: 'var(--v-under)' },
  STALLED:           { label: 'Stalled',               icon: '■', c: 'var(--v-stalled)' },
  MATURE_STABLE:     { label: 'Mature / stable',       icon: '●', c: 'var(--v-mature)' },
  DECLINING:         { label: 'Declining',             icon: '▼', c: 'var(--v-decline)' },
};
// map paint can't use var(), so MapLibre colours are read from the active theme's CSS tokens
const tok = (name) => getComputedStyle(document.documentElement).getPropertyValue(`--${name}`).trim();
const seqRamp = () => [1, 2, 3, 4, 5].map((i) => tok(`seq-${i}`));
const SEQ_CSS = 'linear-gradient(90deg,var(--seq-1),var(--seq-2),var(--seq-3),var(--seq-4),var(--seq-5))';
const DIV_CSS = 'linear-gradient(90deg,var(--div-neg),var(--div-mid),var(--div-pos))';
// the overlay legends below describe pre-rendered PNGs, so their colours stay fixed in both themes
const LEGENDS = {
  score: () => metricLegend(),
  rgb: () => `<div class="title">Sentinel-2 true colour (cloud-masked annual median)</div><div class="fade"><b>${state.base}</b><input type="range" id="fade" min="0" max="100" value="100"><b>${state.latest}</b></div><div class="muted">Drag to cross-fade between years.</div>`,
  new_built: () => `<div class="title">Dynamic World change ${state.base}→${state.latest}</div><div class="swatches"><div><i style="background:#6b6a65"></i>Built in both years</div><div><i style="background:#199e70"></i>Newly built</div><div><i style="background:#d95926"></i>Newly cleared / bare land</div></div>`,
  d_ndbi: () => `<div class="title">Δ NDBI built-up index ${state.base}→${state.latest}</div><div class="ramp" style="background:linear-gradient(90deg,#3987e5,#383835,#e66767)"></div><div class="ramp-labels"><span>−0.25 less built</span><span>no change</span><span>+0.25 more built</span></div>`,
  d_ndvi: () => `<div class="title">Δ NDVI vegetation index ${state.base}→${state.latest}</div><div class="ramp" style="background:linear-gradient(90deg,#e66767,#383835,#3987e5)"></div><div class="ramp-labels"><span>−0.3 vegetation loss</span><span>no change</span><span>+0.3 gain</span></div>`,
  night_lights: () => `<div class="title">VIIRS night-time lights, 12 months to ${state.overlays?.night_lights_until ?? ''}</div><div class="ramp" style="background:linear-gradient(90deg,#1a1a19,#5c3d00,#c98500,#fab219,#fff1c2)"></div><div class="ramp-labels"><span>0</span><span>40+ nW/cm²/sr</span></div>`,
};
const openHotels = () => (state.hotels || []).filter((h) => h.status === 'OPERATIONAL');
const HOTEL_LEGEND = () => `<div class="swatches" style="margin-top:8px;border-top:1px solid var(--grid);padding-top:8px"><div><i style="background:var(--hotel-open);border-radius:50%;box-shadow:0 0 0 1.5px var(--hotel-ring)"></i>Hotel operating now · ${openHotels().length} (Google Places, Oct 2026)</div></div>`;

const state = { layer: 'score', selected: null, base: 2019, latest: 2025, metric: 'score', filter: { verdicts: null, area: 'all', minScore: 0 } };
const ISLANDS = new Set(['belakang-padang', 'bulang', 'galang']);
const plus = (v, d, u = '') => (v === null || v === undefined || Number.isNaN(v)) ? '—' : `${v > 0 ? '+' : ''}${Number(v).toFixed(d)}${u}`;
const popOf = (z) => z.signals.population_ghsl_modelled?.['2025'];
// "Colour kecamatan by": every measure the dashboard can map
const METRICS = {
  score:      { label: 'Gemma 4 growth score', kind: 'seq', get: (z) => z.gemma.growth_score, fmt: (v) => fmt(v, 0) },
  built:      { label: 'Built-up share % (Dynamic World)', kind: 'seq', get: (z) => z.signals.dynamic_world_built_share_pct?.to, fmt: (v) => fmt(v, 0, '%') },
  built_rel:  { label: 'Built-up change vs Batam (pp)', kind: 'div', get: (z) => z.signals.change_relative_to_batam?.dw_built, fmt: (v) => plus(v, 1, ' pp'), note: 'Blue = built up faster than Batam overall.' },
  lights_rel: { label: 'Night-light change vs Batam (pp)', kind: 'div', get: (z) => z.signals.change_relative_to_batam?.viirs, fmt: (v) => plus(v, 0, ' pp'), note: 'Blue = brightened faster than Batam overall.' },
  ndvi_rel:   { label: 'Vegetation (NDVI) change vs Batam', kind: 'div', get: (z) => z.signals.change_relative_to_batam?.ndvi, fmt: (v) => plus(v, 3), note: 'Red = lost vegetation relative to Batam.' },
  lights:     { label: 'Night-light radiance (latest year)', kind: 'seq', get: (z) => z.signals.night_lights_radiance?.to, fmt: (v) => fmt(v, 1) },
  density:    { label: 'Population density /km² (GHSL model)', kind: 'seq', get: (z) => popOf(z) ? popOf(z) / z.signals.area_km2 : null, fmt: (v) => v == null ? '—' : Math.round(v).toLocaleString('id-ID') },
  pop_growth: { label: 'Population growth 2020→2025 %', kind: 'seq', get: (z) => z.signals.population_ghsl_modelled?.change_pct, fmt: (v) => plus(v, 1, '%') },
  decade_lights:     { label: 'Night-light change 2016→2025 %', kind: 'seq', get: (z) => z.signals.decade_2016_2025?.night_lights_radiance?.change_pct, fmt: (v) => plus(v, 0, '%') },
  decade_lights_rel: { label: 'Night-light change 2016→2025 vs Batam (pp)', kind: 'div', get: (z) => z.signals.decade_relative_to_batam?.viirs, fmt: (v) => plus(v, 0, ' pp'), note: 'Blue = brightened faster than Batam over the decade.' },
  decade_bldg:       { label: 'Building cover change 2016→2023 (pp)', kind: 'seq', get: (z) => z.signals.decade_2016_2025?.open_buildings_cover_pct_2016_2023?.delta_pp, fmt: (v) => plus(v, 2, ' pp') },
  decade_pop:        { label: 'Population growth 2015→2025 %', kind: 'seq', get: (z) => z.signals.decade_2016_2025?.population_ghsl_modelled?.change_pct, fmt: (v) => plus(v, 1, '%') },
  survival:   { label: 'Hotels still operating Oct 2026 (% of Jan 2024 list)', kind: 'seq', get: (z) => z.signals.hotels?.hotel_survival_pct, fmt: (v) => fmt(v, 0, '%'), note: 'Grey = no hotels in the 2024 list.' },
  fnb:        { label: 'F&B places growth 2016→2026 % (OSM)', kind: 'seq', get: (z) => { const c = z.signals.decade_2016_2025?.osm_counts_2016_2026?.fnb; return c?.['2016'] ? 100 * (c['2026'] - c['2016']) / c['2016'] : null; }, fmt: (v) => plus(v, 0, '%'), note: 'OSM partly reflects mapping effort.' },
};
function metricLegend() {
  const m = METRICS[state.metric]; const [lo, hi] = state.domain || [0, 1];
  const ramp = m.kind === 'div' ? DIV_CSS : SEQ_CSS;
  const opts = Object.entries(METRICS).map(([k, x]) => `<option value="${k}" ${k === state.metric ? 'selected' : ''}>${esc(x.label)}</option>`).join('');
  return `<div class="title">Colour kecamatan by</div><select id="metric" aria-label="Map measure">${opts}</select>
    <div class="ramp" style="background:${ramp}"></div>
    <div class="ramp-labels"><span>${m.fmt(lo)}</span>${m.kind === 'div' ? '<span>0</span>' : ''}<span>${m.fmt(hi)}</span></div>
    <div class="muted" style="margin-top:4px">${m.note ? esc(m.note) + ' ' : ''}Click a kecamatan for its profile.${state.metric === 'score' ? ' <button class="info-btn" type="button" onclick="openScoreHelp()">How is the score made?</button>' : ''}</div>`;
}
function passes(id) {
  const z = state.analysis?.zones?.[id]; if (!z) return true;
  const f = state.filter;
  if (f.verdicts && !f.verdicts.has(z.gemma.verdict)) return false;
  if (f.area === 'mainland' && ISLANDS.has(id)) return false;
  if (f.area === 'islands' && !ISLANDS.has(id)) return false;
  return (z.gemma.growth_score ?? 0) >= f.minScore;
}
const fillOpacity = () => state.layer === 'score'
  ? ['case', ['!', ['to-boolean', ['get', 'pass']]], 0.06, ['boolean', ['feature-state', 'hover'], false], 0.95, 0.8]
  : ['case', ['!', ['to-boolean', ['get', 'pass']]], 0, ['boolean', ['feature-state', 'hover'], false], 0.18, 0];
function labelData() {
  return { type: 'FeatureCollection', features: state.zones.features.filter((f) => f.properties.pass).map((f) => ({
    type: 'Feature', properties: { name: f.properties.name, val: f.properties.val, rank: -(f.properties.score ?? 0) },
    geometry: { type: 'Point', coordinates: [f.properties.label_lon, f.properties.label_lat] } })) };
}
function recolor() {
  const m = METRICS[state.metric]; const A = state.analysis?.zones || {}; const vals = [];
  for (const f of state.zones.features) {
    const z = A[f.properties.id]; const v = z ? m.get(z) : null;
    const ok = v !== null && v !== undefined && !Number.isNaN(v);
    Object.assign(f.properties, { m: ok ? v : 0, hasm: ok, pass: passes(f.properties.id), val: ok ? m.fmt(v) : (state.analysis ? '—' : '') });
    if (ok) vals.push(v);
  }
  let lo = vals.length ? Math.min(...vals) : 0, hi = vals.length ? Math.max(...vals) : 1;
  if (m.kind === 'div') { const a = Math.max(Math.abs(lo), Math.abs(hi)) || 1; lo = -a; hi = a; }
  if (hi === lo) hi = lo + 1;
  state.domain = [lo, hi];
  if (!map || !map.getSource('zones')) return;
  const seq = seqRamp();
  const color = m.kind === 'div'
    ? ['interpolate', ['linear'], ['get', 'm'], lo, tok('div-neg'), 0, tok('div-mid'), hi, tok('div-pos')]
    : ['interpolate', ['linear'], ['get', 'm'], ...seq.flatMap((c, i) => [lo + (hi - lo) * i / (seq.length - 1), c])];
  map.setPaintProperty('zone-fill', 'fill-color', ['case', ['to-boolean', ['get', 'hasm']], color, tok('no-data')]);
  map.getSource('zones').setData(state.zones);
  map.getSource('zone-labels').setData(labelData());
  if (map.getLayer('hotels')) map.setFilter('hotels', ['in', ['get', 'zone'], ['literal', state.zones.features.filter((f) => f.properties.pass).map((f) => f.properties.id)]]);
  map.setPaintProperty('zone-fill', 'fill-opacity', fillOpacity());
  if (state.layer === 'score') renderLegend();
}
function renderFilters() {
  const a = state.analysis; if (!a) return;
  const counts = {};
  for (const z of Object.values(a.zones)) counts[z.gemma.verdict] = (counts[z.gemma.verdict] || 0) + 1;
  if (!state.filter.verdicts) state.filter.verdicts = new Set(Object.keys(counts));
  $('f-verdict').innerHTML = Object.keys(counts).map((v) => {
    const m = VERDICT[v] || { label: v, icon: '•', c: 'var(--text-muted)' };
    return `<span class="badge ${state.filter.verdicts.has(v) ? '' : 'off'}" data-v="${esc(v)}" data-icon="${m.icon}" style="--c:${m.c}" role="button" tabindex="0" aria-pressed="${state.filter.verdicts.has(v)}">${esc(m.label)} · ${counts[v]}</span>`;
  }).join('');
  $('f-verdict').querySelectorAll('.badge').forEach((el) => el.addEventListener('click', () => {
    const set = state.filter.verdicts; set.has(el.dataset.v) ? set.delete(el.dataset.v) : set.add(el.dataset.v);
    renderFilters(); applyFilters();
  }));
  $('f-area').querySelectorAll('button').forEach((b) => b.classList.toggle('on', b.dataset.v === state.filter.area));
  $('f-score').value = state.filter.minScore; $('f-score-v').textContent = state.filter.minScore;
}
function wireFilters() {
  $('f-area').querySelectorAll('button').forEach((b) => b.addEventListener('click', () => { state.filter.area = b.dataset.v; renderFilters(); applyFilters(); }));
  $('f-score').addEventListener('input', (e) => { state.filter.minScore = Number(e.target.value); $('f-score-v').textContent = e.target.value; applyFilters(); });
  $('f-reset').addEventListener('click', () => { state.filter = { verdicts: null, area: 'all', minScore: 0 }; renderFilters(); applyFilters(); });
}
function applyFilters() { recolor(); renderRanking(); }
function renderRanking() {
  const a = state.analysis; if (!a) return;
  const order = rankingOrder(), shown = order.filter((r) => passes(r.id));
  $('rank-count').textContent = shown.length;
  $('ranking').innerHTML = shown.length ? shown.map((r) => {
    const z = a.zones[r.id]; const s = z.gemma.growth_score ?? 0;
    return `<li class="rank-item ${r.id === state.selected ? 'active' : ''}" data-id="${z.id}" title="${esc(r.why || z.gemma.headline)}">
      <span class="rank-no">${order.indexOf(r) + 1}</span>
      <div><div class="rank-name">${esc(z.name)}</div><div class="rank-sub">${badge(z.gemma.verdict)}<span class="bar"><i style="width:${s}%"></i></span></div></div>
      <span class="rank-score">${s}</span></li>`;
  }).join('') : '<li class="muted" style="padding:8px">No kecamatan match these filters.</li>';
  $('ranking').querySelectorAll('.rank-item').forEach((el) => el.addEventListener('click', () => select(el.dataset.id, true)));
}
const $ = (id) => document.getElementById(id);
const fmt = (v, d = 1, suffix = '') => (v === null || v === undefined || Number.isNaN(v)) ? '—' : `${Number(v).toFixed(d)}${suffix}`;
const signed = (v, d = 1, suffix = '') => (v === null || v === undefined) ? '—' : `${v > 0 ? '▲ +' : v < 0 ? '▼ ' : ''}${Number(v).toFixed(d)}${suffix}`;
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const badge = (v) => { const m = VERDICT[v] || { label: v || 'n/a', icon: '•', c: 'var(--text-muted)' }; return `<span class="badge" data-icon="${m.icon}" style="--c:${m.c}">${esc(m.label)}</span>`; };

async function getJSON(url) { const r = await fetch(url, { cache: 'no-cache' }); if (!r.ok) throw new Error(url); return r.json(); }

async function boot() {
  const [zones, analysis, overlays, hotels] = await Promise.all([
    getJSON('/data/zones.geojson'),
    getJSON('/data/analysis.json').catch(() => null),
    getJSON('/data/overlays.json').catch(() => null),
    getJSON('/data/hotels.json').catch(() => []),
  ]);
  Object.assign(state, { zones, analysis, overlays, hotels });
  if (!hotels.length) $('hotels-toggle').closest('label').style.display = 'none';
  if (analysis) { state.base = analysis.base_year; state.latest = analysis.latest_year; $('model-chip').textContent = `Reasoned by Gemma 4 · ${analysis.model}`; }
  for (const f of zones.features) {
    const z = analysis?.zones?.[f.properties.id];
    f.properties.score = z?.gemma?.growth_score ?? 0;
    f.properties.verdict = z?.gemma?.verdict ?? '';
    Object.assign(f.properties, { pass: true, hasm: false, m: 0, val: '' });
  }
  renderCity();
  wireFilters();
  recolor();
  initMap();
  renderSuggest();
}

function rankingOrder() {
  const a = state.analysis;
  if (!a) return [];
  const r = a.city?.ranking?.filter((x) => a.zones[x.id]);
  if (r?.length) return r.map((x) => ({ id: x.id, why: x.why }));
  return Object.values(a.zones).sort((p, q) => (q.gemma.growth_score ?? 0) - (p.gemma.growth_score ?? 0)).map((z) => ({ id: z.id }));
}

function renderCity() {
  const a = state.analysis;
  if (!a) { $('city-headline').textContent = 'Run the pipeline to generate the analysis.'; return; }
  const c = a.city || {};
  $('city-headline').textContent = c.city_headline || 'Batam growth assessment';
  $('city-brief').textContent = c.city_brief || '';
  const name = (id) => a.zones[id]?.name ?? id;
  $('callouts').innerHTML = [
    c.city_decade_story ? `<div class="callout decade"><b>A decade of Batam · 2016 → 2025</b><br>${esc(c.city_decade_story)}</div>` : '',
    c.top_opportunity ? `<div class="callout"><b>Top opportunity · ${esc(name(c.top_opportunity.id))}</b><br>${esc(c.top_opportunity.text)}</div>` : '',
    c.top_risk ? `<div class="callout risk"><b>Top risk · ${esc(name(c.top_risk.id))}</b><br>${esc(c.top_risk.text)}</div>` : '',
  ].join('');
  renderFilters();
  renderRanking();
}

let map;
const BASEMAP = { dark: 'https://tiles.openfreemap.org/styles/dark', light: 'https://tiles.openfreemap.org/styles/positron' };  // OpenFreeMap: open-source vector tiles, no API key
function initMap() {
  map = new maplibregl.Map({
    container: 'map', attributionControl: { compact: true, customAttribution: 'Contains modified Copernicus Sentinel data · HDX COD-AB boundaries' },
    style: BASEMAP[theme()],
    bounds: bboxOf(state.zones), fitBoundsOptions: { padding: phone.matches ? { top: 90, bottom: 80, left: 12, right: 12 } : { top: 70, bottom: 110, left: 40, right: 40 } },
  });
  map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');
  map.on('style.load', addDataLayers);  // also fires after a theme swap, which replaces the whole style
  map.once('load', () => {
    wireMapEvents();
    const first = rankingOrder()[0]; if (first) select(first.id);
  });
}

function addDataLayers() {
  // keep the basemap quiet: hide its own labels, ours sit on top
  for (const l of map.getStyle().layers) if (l.type === 'symbol') map.setLayoutProperty(l.id, 'visibility', 'none');
  const ov = state.overlays;
  if (ov) {
    const [x0, y0, x1, y1] = ov.bbox;
    const coords = [[x0, y1], [x1, y1], [x1, y0], [x0, y0]];
    for (const name of ov.layers) {
      map.addSource(`ov-${name}`, { type: 'image', url: `/data/img/overlay_${name}.${name.startsWith('rgb_') ? 'jpg' : 'png'}`, coordinates: coords });
      map.addLayer({ id: `ov-${name}`, type: 'raster', source: `ov-${name}`, layout: { visibility: 'none' }, paint: { 'raster-opacity': 0.92, 'raster-fade-duration': 0 } });
    }
  }
  map.addSource('zones', { type: 'geojson', data: state.zones, promoteId: 'id' });
  map.addLayer({ id: 'zone-fill', type: 'fill', source: 'zones', paint: { 'fill-color': tok('no-data'), 'fill-opacity': fillOpacity() } });  // recolor() sets the ramp
  map.addLayer({ id: 'zone-line', type: 'line', source: 'zones', paint: { 'line-color': tok('zone-gap'), 'line-width': 1.2,
    'line-opacity': ['case', ['to-boolean', ['get', 'pass']], 1, 0.25] } });
  map.addLayer({ id: 'zone-sel', type: 'line', source: 'zones', filter: ['==', ['get', 'id'], state.selected ?? ''], paint: { 'line-color': tok('accent'), 'line-width': 3 } });

  map.addSource('hotels', { type: 'geojson', data: { type: 'FeatureCollection', features: openHotels().map((h) => ({ type: 'Feature', properties: h, geometry: { type: 'Point', coordinates: [h.lon, h.lat] } })) } });
  map.addLayer({ id: 'hotels', type: 'circle', source: 'hotels', layout: { visibility: $('hotels-toggle').checked ? 'visible' : 'none' }, paint: {
    'circle-radius': ['interpolate', ['linear'], ['zoom'], 10, 2.5, 14, 6],
    'circle-color': tok('hotel-open'),
    'circle-stroke-color': tok('hotel-ring'), 'circle-stroke-width': 1 } });

  map.addSource('zone-labels', { type: 'geojson', data: labelData() });
  map.addLayer({ id: 'zone-labels', type: 'symbol', source: 'zone-labels', layout: {
    'text-field': ['format', ['get', 'name'], { 'font-scale': 0.9 }, '\n', {}, ['get', 'val'], { 'font-scale': 1.15, 'text-color': tok('accent-strong') }],
    'text-font': ['Noto Sans Regular'], 'text-size': 12, 'text-line-height': 1.15, 'symbol-sort-key': ['get', 'rank'], 'text-padding': 4 },
    paint: { 'text-color': tok('text-primary'), 'text-halo-color': tok('label-halo'), 'text-halo-width': 1.6 } });
  setLayer(state.layer);
  recolor();
}

function bboxOf(fc) {
  let b = [180, 90, -180, -90];
  const walk = (c) => typeof c[0] === 'number' ? (b = [Math.min(b[0], c[0]), Math.min(b[1], c[1]), Math.max(b[2], c[0]), Math.max(b[3], c[1])]) : c.forEach(walk);
  fc.features.forEach((f) => walk(f.geometry.coordinates));
  return [[b[0], b[1]], [b[2], b[3]]];
}

function wireMapEvents() {
  const tip = $('map-tip'); let hovered = null;
  map.on('mousemove', 'zone-fill', (e) => {
    if (touch.matches) return;  // a tap would leave the hover tip stuck on screen; phones get the map card instead
    const f = e.features[0]; map.getCanvas().style.cursor = 'pointer';
    if (hovered !== f.id) { if (hovered) map.setFeatureState({ source: 'zones', id: hovered }, { hover: false }); hovered = f.id; map.setFeatureState({ source: 'zones', id: hovered }, { hover: true }); }
    const z = state.analysis?.zones?.[f.properties.id];
    const mline = state.layer === 'score' && state.metric !== 'score' ? `<div>${esc(METRICS[state.metric].label)}: <b>${esc(f.properties.val)}</b></div>` : '';
    tip.innerHTML = `<b>${esc(f.properties.name)}</b>${mline}<div style="margin:4px 0">${z ? badge(z.gemma.verdict) : ''}</div>${z ? `<div>Score <b>${z.gemma.growth_score}</b> · ${esc(z.gemma.confidence || '')} confidence</div><div class="muted">${esc(z.gemma.headline)}</div>` : ''}`;
    tip.style.display = 'block'; tip.style.left = `${e.point.x + 14}px`; tip.style.top = `${e.point.y + 14}px`;
  });
  map.on('mouseleave', 'zone-fill', () => { if (hovered) map.setFeatureState({ source: 'zones', id: hovered }, { hover: false }); hovered = null; tip.style.display = 'none'; map.getCanvas().style.cursor = ''; });
  map.on('click', 'zone-fill', (e) => select(e.features[0].properties.id, false, !phone.matches));  // phones stay on the map and show the card
  map.on('mouseenter', 'hotels', () => map.getCanvas().style.cursor = 'help');
  map.on('click', 'hotels', (e) => {
    const h = e.features[0].properties;
    new maplibregl.Popup({ closeButton: false }).setLngLat(e.lngLat).setHTML(`<div style="color:#0b0b0b"><b>${esc(h.name)}</b><br>${h.stars ? '★'.repeat(h.stars) + ' · ' : ''}Operating · Google Places, Oct 2026</div>`).addTo(map);
  });
  $('layer-ctl').querySelectorAll('button').forEach((b) => b.addEventListener('click', () => setLayer(b.dataset.layer)));
  $('hotels-toggle').addEventListener('change', (e) => { map.setLayoutProperty('hotels', 'visibility', e.target.checked ? 'visible' : 'none'); renderLegend(); });
}

function setLayer(layer) {
  state.layer = layer;
  $('layer-ctl').querySelectorAll('button').forEach((b) => b.classList.toggle('on', b.dataset.layer === layer));
  const ov = state.overlays?.layers || [];
  for (const name of ov) map.setLayoutProperty(`ov-${name}`, 'visibility', 'none');
  const show = (n) => ov.includes(n) && map.setLayoutProperty(`ov-${n}`, 'visibility', 'visible');
  if (layer === 'rgb') { show(`rgb_${state.base}`); show(`rgb_${state.latest}`); }
  else if (layer !== 'score') show(layer);
  map.setPaintProperty('zone-fill', 'fill-opacity', fillOpacity());
  map.setPaintProperty('zone-line', 'line-color', tok(layer === 'score' ? 'zone-gap' : 'zone-line-overlay'));
  renderLegend();
}

function renderLegend() {
  $('legend').innerHTML = LEGENDS[state.layer]() + ($('hotels-toggle').checked ? HOTEL_LEGEND() : '');
  $('metric')?.addEventListener('change', (e) => { state.metric = e.target.value; recolor(); });
  const fade = $('fade');
  if (fade) fade.addEventListener('input', (e) => map.setPaintProperty(`ov-rgb_${state.latest}`, 'raster-opacity', e.target.value / 100));
}

// open: bring the district panel into view on tablets and phones (and start it from the top for a new zone)
function select(id, fly = false, open = fly) {
  const z = state.analysis?.zones?.[id]; if (!z) return;
  const fresh = id !== state.selected;
  state.selected = id;
  map?.setFilter('zone-sel', ['==', ['get', 'id'], id]);
  document.querySelectorAll('.rank-item').forEach((el) => el.classList.toggle('active', el.dataset.id === id));
  if (fly) document.querySelector(`.rank-item[data-id="${id}"]`)?.scrollIntoView({ block: 'nearest' });
  if (fly) { const f = state.zones.features.find((x) => x.properties.id === id); map.fitBounds(bboxOf({ features: [f] }), { padding: phone.matches ? 40 : 120, maxZoom: 13, duration: 800 }); }
  renderDetail(z);
  showTab('detail');
  $('map-peek').innerHTML = `<span class="pk-main"><span class="pk-name">${esc(z.name)}</span>${badge(z.gemma.verdict)}</span><span class="pk-score">${z.gemma.growth_score ?? '—'}</span><span class="pk-go">Details ›</span>`;
  renderSuggest();
  if (open) { setView('detail'); if (fresh) $('detail').scrollTop = 0; }
}

function sparkline(series, key, scale = 1, digits = 2, unit = '') {
  const pts = Object.entries(series).map(([y, v]) => [Number(y), v[key] == null ? null : v[key] * scale]).filter((p) => p[1] !== null);
  if (pts.length < 2) return '<div class="muted" style="font-size:11px;margin-top:10px">no data</div>';
  const W = 180, H = 44, xs = pts.map((p) => p[0]), ys = pts.map((p) => p[1]);
  const x0 = Math.min(...xs), x1 = Math.max(...xs), lo = Math.min(...ys), hi = Math.max(...ys), pad = (hi - lo) * 0.15 || Math.abs(hi) * 0.05 || 1;
  const X = (x) => 4 + (x - x0) / (x1 - x0 || 1) * (W - 8), Y = (y) => H - 4 - (y - (lo - pad)) / ((hi + pad) - (lo - pad)) * (H - 8);
  const d = pts.map((p, i) => `${i ? 'L' : 'M'}${X(p[0]).toFixed(1)},${Y(p[1]).toFixed(1)}`).join('');
  const dots = pts.map((p) => `<g class="pt"><rect x="${X(p[0]) - 10}" y="0" width="20" height="${H}" fill="transparent"/><circle cx="${X(p[0])}" cy="${Y(p[1])}" r="4" stroke-width="2" opacity="0"/><title>${p[0]}${p[0] === 2026 ? ' (YTD)' : ''}: ${p[1].toFixed(digits)}${unit}</title></g>`).join('');
  return `<svg viewBox="0 0 ${W} ${H}" preserveAspectRatio="none"><line class="base" x1="0" x2="${W}" y1="${H - 1}" y2="${H - 1}"/><path class="line" d="${d}" fill="none" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>${dots}</svg><div class="ramp-labels"><span>${x0}</span><span>${x1}</span></div>`;
}

function profileCard(z) {
  const feat = state.zones.features.find((f) => f.properties.id === z.id)?.properties || {};
  const s = z.signals, h = s.hotels || {}, o = s.osm_business_counts || {};
  const order = rankingOrder().map((r) => r.id), rank = order.indexOf(z.id) + 1, pop = popOf(z);
  const rows = [
    ['Kecamatan P-code', feat.code],
    ['Area', `${fmt(s.area_km2, 2)} km²${ISLANDS.has(z.id) ? ' · hinterland islands' : ' · mainland'}`],
    ['Population 2025 (GHSL model)', pop ? Number(pop).toLocaleString('id-ID') : '—'],
    ['Density', pop ? `${Math.round(pop / s.area_km2).toLocaleString('id-ID')} /km²` : '—'],
    ['Growth rank', rank ? `#${rank} of ${order.length}` : '—'],
    ['Hotels operating now (Oct 2026)', `${(h.hotels_2024_still_open ?? 0) + (h.hotel_type_listings_not_in_2024_scrape ?? 0)}`],
    ['Lodging in OSM, 2016 → 2026', `${s.decade_2016_2025?.osm_counts_2016_2026?.lodging?.['2016'] ?? '—'} → ${s.decade_2016_2025?.osm_counts_2016_2026?.lodging?.['2026'] ?? '—'}`],
    ['OSM F&B · retail · office (2026)', `${o.fnb?.['2026'] ?? '—'} · ${o.retail?.['2026'] ?? '—'} · ${o.office?.['2026'] ?? '—'}`],
    ['Boundary source', feat.source],
    ['Analysed by', `${z.gemma._model || state.analysis.model} · ${state.analysis.generated_at}`],
  ];
  return `<div class="section card" style="margin-top:0;margin-bottom:14px"><div class="eyebrow">District profile</div>
    <table class="profile">${rows.map(([k, v]) => `<tr><td>${esc(k)}</td><td>${esc(v ?? '—')}</td></tr>`).join('')}</table></div>`;
}

// What Gemma weighed, each signal set against Batam overall. Mixed units, so a table with direction
// markers rather than bars on one axis.
function scoreWhy(z) {
  const s = z.signals, r = s.change_relative_to_batam || {}, d = s.decade_relative_to_batam || {};
  const zones = Object.values(state.analysis.zones);
  const mean = (f) => { const v = zones.map(f).filter((x) => x != null); return v.length ? v.reduce((a, b) => a + b, 0) / v.length : null; };
  const vsMean = (f) => { const v = f(z), m = mean(f); return v == null || m == null ? null : v - m; };
  const rows = [
    ['Built-up share 2019→2025', r.dw_built, 1, ' pp', 'vs Batam'],
    ['Night lights 2019→2025', r.viirs, 0, ' pp', 'vs Batam'],
    ['Night lights 2016→2025', d.viirs, 0, ' pp', 'vs Batam'],
    ['Building cover 2016→2023', d.ob_built, 2, ' pp', 'vs Batam'],
    ['Radar backscatter 2019→2025', r.s1_vv, 2, ' dB', 'vs Batam'],
    ['Cleared / bare land 2019→2025', r.dw_bare, 1, ' pp', 'vs Batam'],
    ['Vegetation (NDVI) 2019→2025', r.ndvi, 3, '', 'vs Batam'],
    ['Hotels still operating Oct 2026 (of Jan 2024 list)', vsMean((x) => x.signals.hotels?.hotel_survival_pct), 0, ' pp', 'vs kec. average'],
    ['Population growth 2020→2025', vsMean((x) => x.signals.population_ghsl_modelled?.change_pct), 1, ' pp', 'vs kec. average'],
  ].filter((row) => row[1] != null);
  const dir = (v, dd, u) => Math.abs(v) < 0.5 * 10 ** -dd ? '≈ same' : `${v > 0 ? '▲ +' : '▼ '}${Number(v).toFixed(dd)}${u}`;
  return `<div class="section card" style="margin-top:0;margin-bottom:14px">
    <div class="eyebrow" style="display:flex;justify-content:space-between">Where the score comes from <button class="info-btn" type="button" onclick="openScoreHelp()">How scoring works</button></div>
    <p class="prose" style="margin:0 0 8px">Gemma 4 set <b style="color:var(--text-primary)">${z.gemma.growth_score ?? '—'}</b> after weighing these signals, each compared with Batam overall, together with five satellite images. ▲ = higher than Batam.</p>
    <table class="why-t">${rows.map(([k, v, dd, u, c]) => `<tr><td>${esc(k)}</td><td class="v">${dir(v, dd, u)}</td><td class="c">${c}</td></tr>`).join('')}</table>
    <p class="prose" style="margin:8px 0 0">Its full reasoning and the evidence it ranked highest are further down this panel.</p></div>`;
}

function openScoreHelp() {
  const a = state.analysis || {};
  const used = a.models_used ? Object.entries(a.models_used).map(([m, n]) => `${esc(m)} (${n} kecamatan)`).join(', ') : esc(a.model || 'Gemma 4');
  const verdicts = Object.entries(a.verdicts || {}).map(([k, v]) => `<div>${badge(k)}<span>${esc(v)}</span></div>`).join('');
  $('score-help-body').innerHTML = `
    <p><b>The score is Gemma 4's judgement, not a fixed formula.</b> A formula would say "built-up went up, so growth". Gemma has to check whether that growth is real.</p>
    <p><b>What Gemma receives for each kecamatan</b></p>
    <ul>
      <li>About 40 numbers: satellite change ${a.base_year ?? 2019}→${a.latest_year ?? 2025} (Sentinel-2 indices, Dynamic World land cover, radar, night lights, land temperature) and the decade 2016→2025 (Landsat, night lights, radar, Open Buildings, population). Each change is also given <i>relative to Batam overall</i>, so a dry year or a city-wide shift does not count as local growth.</li>
      <li>Ground truth: how many of the Jan 2024 hotels still operate in Oct 2026 (Google Places), and OSM business counts.</li>
      <li>Five images: Sentinel-2 true colour ${a.base_year ?? 2019} and ${a.latest_year ?? 2025}, a built-up change map, and Landsat 2016 and 2025.</li>
    </ul>
    <p><b>The instruction it gets:</b> "growth_score: 0–100 = realized + near-term growth momentum". It must also pick a verdict, say what it sees in the images, explain any conflicting signals, and list the evidence it weighed. All of this is shown in the district panel.</p>
    <p><b>Verdicts</b></p><div class="vlist">${verdicts}</div>
    <p><b>Ranking:</b> each kecamatan is scored in its own call; a final Gemma call compares all 12 and writes the ranking and city brief.</p>
    <p class="muted" style="font-size:12px">Scored by ${used}. Scores are indicators for comparing kecamatan, not official statistics.</p>`;
  $('score-help').showModal();
}

function decadeSection(z) {
  const d = z.signals.decade_2016_2025; if (!d) return '';
  const R = state.analysis.batam_wide_decade_change || {}, o = d.osm_counts_2016_2026 || {};
  const vs = (v, dd, suf = '') => v == null ? '' : ` · Batam ${signed(v, dd, suf)}`;
  const pop = d.population_ghsl_modelled || {};
  const tiles = [
    ['Night lights (VIIRS)', fmt(d.night_lights_radiance?.['2025'], 1), signed(d.night_lights_radiance?.change_pct, 0, '%') + vs(R.viirs, 0, '%')],
    ['Building cover (Open Buildings)', fmt(d.open_buildings_cover_pct_2016_2023?.['2023'], 1, '%'), `${signed(d.open_buildings_cover_pct_2016_2023?.delta_pp, 2, ' pp')} to 2023${vs(R.ob_built, 2)}`],
    ['Radar VV backscatter', fmt(d.radar_vv_db?.['2025'], 1, ' dB'), signed(d.radar_vv_db?.delta_db, 2, ' dB') + vs(R.s1_vv, 2)],
    ['NDBI built-up (Landsat)', fmt(d.landsat_ndbi?.['2025'], 3), signed(d.landsat_ndbi?.delta, 3) + vs(R.l_ndbi, 3)],
    ['NDVI vegetation (Landsat)', fmt(d.landsat_ndvi?.['2025'], 3), signed(d.landsat_ndvi?.delta, 3) + vs(R.l_ndvi, 3)],
    ['Population (GHSL model)', pop['2025'] ? Number(pop['2025']).toLocaleString('id-ID') : '—', `${signed(pop.change_pct, 1, '%')} vs 2015`],
    ['Lodging in OSM', `${o.lodging?.['2016'] ?? '—'} → ${o.lodging?.['2026'] ?? '—'}`, '2016 → 2026 · partly mapping effort'],
    ['Surface temp (Landsat)', fmt(d.land_surface_temp_c?.['2025'], 1, ' °C'), signed(d.land_surface_temp_c?.delta, 1, ' °C') + vs(R.l_lst, 1)],
  ];
  return `<div class="section"><div class="eyebrow">A decade of change · 2016 → 2025</div>
    ${z.gemma.decade_story ? `<p class="prose" style="margin:0 0 10px">${esc(z.gemma.decade_story)}</p>` : ''}
    <div class="tiles">${tiles.map(([k, v, dd]) => `<div class="tile"><div class="k">${k}</div><div class="v">${v}</div><div class="d">${dd}</div></div>`).join('')}</div></div>`;
}

function renderDetail(z) {
  const g = z.gemma, s = z.signals, h = s.hotels || {}, o = g.outlook_2028 || {}, b = state.base, l = state.latest;
  const R = state.analysis.batam_wide_change || {};
  const [cmpA, cmpB, cmpPre] = state.cmp === 'l' ? [2016, 2025, 'D'] : [b, l, ''];
  const vs = (v, d, suf = '') => v == null ? '' : ` · Batam ${signed(v, d, suf)}`;
  const last = (key, scale = 1) => { const v = z.series[String(l)]?.[key]; return v == null ? null : v * scale; };
  const tiles = [
    ['Built-up share (Dynamic World)', fmt(s.dynamic_world_built_share_pct?.to, 1, '%'), `${signed(s.dynamic_world_built_share_pct?.delta_pp, 1, ' pp')}${vs(R.dw_built, 1)}`],
    ['Cleared / bare land', fmt(s.dynamic_world_bare_share_pct?.to, 1, '%'), `${signed(s.dynamic_world_bare_share_pct?.delta_pp, 1, ' pp')}${vs(R.dw_bare, 1)}`],
    ['Night lights', fmt(s.night_lights_radiance?.to, 1), `${signed(s.night_lights_radiance?.change_pct, 0, '%')}${vs(R.viirs, 0, '%')}`],
    ['NDBI built-up index', fmt(s.ndbi?.to, 3), signed(s.ndbi?.delta, 3) + vs(R.ndbi, 3)],
    ['NDVI vegetation', fmt(s.ndvi?.to, 3), signed(s.ndvi?.delta, 3) + vs(R.ndvi, 3)],
    ['Radar VV backscatter', fmt(s.radar_vv_db?.to, 1, ' dB'), signed(s.radar_vv_db?.delta_db, 2, ' dB') + vs(R.s1_vv, 2)],
    ['Hotels operating now', `${(h.hotels_2024_still_open ?? 0) + (h.hotel_type_listings_not_in_2024_scrape ?? 0)}`, 'Google Places, Oct 2026'],
    ['Lodging in OSM, 2016 → 2026', `${s.decade_2016_2025?.osm_counts_2016_2026?.lodging?.['2016'] ?? '—'} → ${s.decade_2016_2025?.osm_counts_2016_2026?.lodging?.['2026'] ?? '—'}`, 'partly mapping effort'],
    ['Population (GHSL model)', s.population_ghsl_modelled?.['2025'] ? Number(s.population_ghsl_modelled['2025']).toLocaleString('id-ID') : '—', `${signed(s.population_ghsl_modelled?.change_pct, 1, '%')} vs 2020`],
    ['Surface temperature', fmt(s.land_surface_temp_c?.to, 1, ' °C'), signed(s.land_surface_temp_c?.delta, 1, ' °C') + vs(R.lst, 1)],
    ['Coastal land gained', fmt(s.coastal_land_gained_km2, 2, ' km²'), 'water→land since ' + b],
    ...['fnb', 'retail', 'office'].map((g) => {
      const c = s.decade_2016_2025?.osm_counts_2016_2026?.[g] || {};
      const lbl = { fnb: 'F&B places (OSM)', retail: 'Retail places (OSM)', office: 'Offices (OSM)' }[g];
      return [lbl, `${c['2016'] ?? '—'} → ${c['2026'] ?? '—'}`, '2016 → 2026 · partly mapping effort'];
    }),
    ['Building cover (Open Buildings)', fmt(s.open_buildings_cover_pct_2019_2023?.to, 1, '%'), `${signed(s.open_buildings_cover_pct_2019_2023?.delta_pp, 2, ' pp')} 2019→2023`],
  ];
  const ev = (g.evidence || []).map((e) => `<tr><td>${esc(e.signal)}</td><td>${esc(e.reading)}<br><span class="muted">${esc(e.meaning)}</span></td></tr>`).join('');
  $('detail').innerHTML = `
    <div class="eyebrow">Kecamatan · ${esc(z.signals.area_km2)} km²</div>
    <div class="zone-head"><div><h1 class="zone-name">${esc(z.name)}</h1><div style="margin-top:6px">${badge(g.verdict)} <span class="muted" style="font-size:12px">· ${esc(g.confidence || '')} confidence</span></div></div>
      <div class="score-big"><div class="n">${g.growth_score ?? '—'}</div><div class="l">growth score<button class="info-btn" type="button" onclick="openScoreHelp()" aria-label="How the growth score is made">ⓘ</button></div></div></div>
    <div class="headline">“${esc(g.headline)}”</div>
    ${scoreWhy(z)}
    ${profileCard(z)}
    <div class="seg cmp-seg" id="cmp-seg"><button type="button" data-v="s2" class="${state.cmp !== 'l' ? 'on' : ''}">${b} → ${l} · recent</button><button type="button" data-v="l" class="${state.cmp === 'l' ? 'on' : ''}">2016 → 2025 · decade</button></div>
    <div class="muted" style="font-size:11px;margin:-2px 0 8px">2025 is the latest full year; 2026 (Jan–Oct) appears in the trend lines.</div>
    <div class="compare" id="compare">
      <img src="/data/img/${z.id}_${cmpPre}${cmpA}.jpg" alt="${esc(z.name)} ${cmpA}"><img class="after" id="after" src="/data/img/${z.id}_${cmpPre}${cmpB}.jpg" alt="${esc(z.name)} ${cmpB}">
      <div class="handle" id="handle"></div><span class="tag l">${cmpA}</span><span class="tag r">${cmpB}</span>
      <input type="range" min="0" max="100" value="50" id="cmp" aria-label="Before/after slider">
    </div>
    <div class="img-row"><figure><img src="/data/img/${z.id}_change.png" alt="change map"><figcaption><span style="color:#199e70">■</span> newly built · <span style="color:#d95926">■</span> newly cleared · <span style="color:#6b6a65">■</span> built both</figcaption></figure>
      <div class="card" style="padding:10px"><div class="eyebrow">What Gemma sees</div><div style="font-size:12px;color:var(--text-secondary)">${esc(g.image_observations)}</div></div></div>

    <div class="section"><div class="eyebrow">Gemma 4 reasoning</div><p class="prose">${esc(g.reasoning)}</p>
      ${g.conflicts && g.conflicts !== 'none' ? `<p class="prose"><b style="color:var(--text-primary)">Signal conflict resolved:</b> ${esc(g.conflicts)}</p>` : ''}</div>

    ${decadeSection(z)}

    <div class="section"><div class="eyebrow">Signals ${b} → ${l} · change vs Batam-wide change</div><div class="tiles">${tiles.map(([k, v, d]) => `<div class="tile"><div class="k">${k}</div><div class="v">${v}</div><div class="d">${d}</div></div>`).join('')}</div></div>

    <div class="section"><div class="eyebrow">Trends ${b}–2026 (hover for values)</div><div class="sparks">
      <div class="spark"><div class="k">Built-up share <b>${fmt(last('dw_built', 100), 1, '%')}</b></div>${sparkline(z.series, 'dw_built', 100, 1, '%')}</div>
      <div class="spark"><div class="k">Night lights <b>${fmt(last('viirs'), 1)}</b></div>${sparkline(z.series, 'viirs', 1, 1)}</div>
      <div class="spark"><div class="k">NDBI <b>${fmt(last('ndbi'), 3)}</b></div>${sparkline(z.series, 'ndbi', 1, 3)}</div>
      <div class="spark"><div class="k">NDVI <b>${fmt(last('ndvi'), 3)}</b></div>${sparkline(z.series, 'ndvi', 1, 3)}</div>
    </div></div>

    <div class="section"><div class="eyebrow">Evidence Gemma weighed</div><table class="evidence">${ev}</table></div>

    <div class="section card"><div class="eyebrow">2028 outlook (Gemma forecast)</div>
      <div class="outlook"><div class="n">${fmt(o.built_share_2028_pct, 1, '%')}</div><div><div style="font-weight:600;text-transform:capitalize">${esc(o.trajectory || '')}</div><div class="muted" style="font-size:12px">built-up share · linear baseline ${fmt(s.built_share_2028_linear_baseline_pct, 1, '%')}</div></div></div>
      <p class="prose">${esc(o.rationale)}</p></div>

    <div class="section card reco"><div class="eyebrow">Recommendation for BP Batam / Pemko Batam</div><p class="prose" style="color:var(--text-primary)">${esc(g.recommendation)}</p>
      <p class="prose"><b>Watch next:</b> ${esc(g.watch_next)}</p></div>`;
  $('cmp-seg').querySelectorAll('button').forEach((btn) => btn.addEventListener('click', () => { state.cmp = btn.dataset.v; renderDetail(z); }));
  const setCmp = (v) => { $('cmp').value = v; $('after').style.clipPath = `inset(0 0 0 ${v}%)`; $('handle').style.left = `${v}%`; };
  $('cmp').addEventListener('input', (e) => setCmp(e.target.value));
  // touch: drag anywhere on the image (iOS only moves a range input from its thumb, which is invisible here)
  const dragCmp = (e) => { if (e.pointerType === 'mouse' || !e.buttons) return; const r = $('compare').getBoundingClientRect(); setCmp(Math.round(Math.min(100, Math.max(0, 100 * (e.clientX - r.left) / r.width)))); };
  $('compare').addEventListener('pointerdown', dragCmp);
  $('compare').addEventListener('pointermove', dragCmp);
  document.querySelectorAll('.spark .pt').forEach((p) => { p.addEventListener('mouseenter', () => p.querySelector('circle').setAttribute('opacity', 1)); p.addEventListener('mouseleave', () => p.querySelector('circle').setAttribute('opacity', 0)); });
}

const COLS = [
  ['Gemma 4', [['Verdict', (z) => VERDICT[z.gemma.verdict]?.label ?? z.gemma.verdict], ['Score', (z) => z.gemma.growth_score], ['Confidence', (z) => z.gemma.confidence], ['2028 built %', (z) => z.gemma.outlook_2028?.built_share_2028_pct]]],
  ['Built environment', [['Built % (DW)', (z) => z.signals.dynamic_world_built_share_pct?.to], ['Δ built pp', (z) => z.signals.dynamic_world_built_share_pct?.delta_pp], ['Δ bare pp', (z) => z.signals.dynamic_world_bare_share_pct?.delta_pp], ['Δ NDBI', (z) => z.signals.ndbi?.delta], ['Δ NDVI', (z) => z.signals.ndvi?.delta], ['Δ radar dB', (z) => z.signals.radar_vv_db?.delta_db], ['Δ bldg cover pp', (z) => z.signals.open_buildings_cover_pct_2019_2023?.delta_pp], ['Coast gained km²', (z) => z.signals.coastal_land_gained_km2], ['Δ LST °C', (z) => z.signals.land_surface_temp_c?.delta]]],
  ['Activity', [['Night lights', (z) => z.signals.night_lights_radiance?.to], ['Δ lights %', (z) => z.signals.night_lights_radiance?.change_pct], ['Pop 2025', (z) => z.signals.population_ghsl_modelled?.['2025']], ['Δ pop %', (z) => z.signals.population_ghsl_modelled?.change_pct]]],
  ['Decade 2016→2025', [['Δ lights %', (z) => z.signals.decade_2016_2025?.night_lights_radiance?.change_pct], ['Δ bldg pp (→2023)', (z) => z.signals.decade_2016_2025?.open_buildings_cover_pct_2016_2023?.delta_pp], ['Δ radar dB', (z) => z.signals.decade_2016_2025?.radar_vv_db?.delta_db], ['Δ NDBI (Landsat)', (z) => z.signals.decade_2016_2025?.landsat_ndbi?.delta], ['Δ pop % (2015→)', (z) => z.signals.decade_2016_2025?.population_ghsl_modelled?.change_pct], ['OSM lodging 2016', (z) => z.signals.decade_2016_2025?.osm_counts_2016_2026?.lodging?.['2016']], ['OSM lodging 2026', (z) => z.signals.decade_2016_2025?.osm_counts_2016_2026?.lodging?.['2026']]]],
  ['Hotels (Places)', [['Operating Oct 2026', (z) => (z.signals.hotels?.hotels_2024_still_open ?? 0) + (z.signals.hotels?.hotel_type_listings_not_in_2024_scrape ?? 0)], ['Survival % of Jan 2024 list', (z) => z.signals.hotels?.hotel_survival_pct]]],
  ['OSM businesses', [['F&B', (z) => z.signals.osm_business_counts?.fnb?.['2026']], ['Δ F&B %', (z) => z.signals.osm_business_counts?.fnb?.growth_pct], ['Retail', (z) => z.signals.osm_business_counts?.retail?.['2026']], ['Δ retail %', (z) => z.signals.osm_business_counts?.retail?.growth_pct], ['Office', (z) => z.signals.osm_business_counts?.office?.['2026']], ['Δ office %', (z) => z.signals.osm_business_counts?.office?.growth_pct]]],
];
const cell = (v) => v === null || v === undefined ? '—' : typeof v === 'number' ? (Number.isInteger(v) ? v.toLocaleString('id-ID') : v.toFixed(Math.abs(v) < 1 ? 3 : 1)) : esc(v);
function openData() {
  const a = state.analysis; if (!a) return;
  const zones = rankingOrder().filter((r) => passes(r.id)).map((r) => a.zones[r.id]);
  $('data-table').innerHTML = `<thead><tr><th></th>${COLS.map(([g, cs]) => `<th class="grp" colspan="${cs.length}">${g}</th>`).join('')}</tr>
    <tr><th>Kecamatan</th>${COLS.flatMap(([, cs]) => cs.map(([h]) => `<th>${h}</th>`)).join('')}</tr></thead>
    <tbody>${zones.map((z) => `<tr data-id="${z.id}" style="cursor:pointer"><td>${esc(z.name)}</td>${COLS.flatMap(([, cs]) => cs.map(([, f]) => `<td>${cell(f(z))}</td>`)).join('')}</tr>`).join('')}</tbody>`;
  $('data-table').querySelectorAll('tbody tr').forEach((tr) => tr.addEventListener('click', () => { $('data').close(); select(tr.dataset.id, true); }));
  $('data').showModal();
}
function downloadCSV() {
  const a = state.analysis; const zones = rankingOrder().filter((r) => passes(r.id)).map((r) => a.zones[r.id]);
  const head = ['Kecamatan', ...COLS.flatMap(([g, cs]) => cs.map(([h]) => `${g}: ${h}`))];
  const rows = zones.map((z) => [z.name, ...COLS.flatMap(([, cs]) => cs.map(([, f]) => f(z) ?? ''))]);
  const csv = [head, ...rows].map((r) => r.map((v) => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n');
  const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
  Object.assign(document.createElement('a'), { href: url, download: 'sigmax_batam_signals.csv' }).click();
}

const SUGGESTED = ['Where should BP Batam prioritise infrastructure next?', 'Kecamatan mana yang stagnan, dan kenapa?', 'Is the growth in the top zone real or just land clearing?'];
function zoneQuestion() {
  const z = state.analysis?.zones?.[state.selected];
  return z ? `Why is ${z.name} rated “${VERDICT[z.gemma.verdict]?.label ?? z.gemma.verdict}”, and what should change there?` : null;
}
function renderSuggest() {
  const qs = [...SUGGESTED, zoneQuestion()].filter(Boolean);
  $('suggest').innerHTML = qs.map((q) => `<button type="button">${esc(q)}</button>`).join('');
  $('suggest').querySelectorAll('button').forEach((b) => b.addEventListener('click', () => { $('ask-input').value = b.textContent; $('ask-form').requestSubmit(); }));
}
function showTab(id) {
  document.querySelectorAll('.tabs button').forEach((b) => { const on = b.dataset.tab === id; b.classList.toggle('on', on); b.setAttribute('aria-selected', on); });
  document.querySelectorAll('aside.right .pane').forEach((p) => { p.hidden = p.id !== id; });
  if (id === 'ask-pane' && !touch.matches) $('ask-input').focus();  // on touch screens focusing would pop the keyboard over the suggestions
}
document.querySelectorAll('.tabs button').forEach((b) => b.addEventListener('click', () => setView(b.dataset.tab === 'ask-pane' ? 'ask' : 'detail')));

// Below 1100px the panels take turns: tablets keep the map beside one panel, phones show one view at a time.
// The view (map | brief | detail | ask) lives on <main data-view> and the CSS decides what that shows at each width.
const phone = matchMedia('(max-width: 720px)'), touch = matchMedia('(hover: none)');
function setView(v) {
  if (v === 'map' && !phone.matches) v = 'brief';  // the map is always visible beside the panel on wider screens
  $('main').dataset.view = v;
  document.querySelectorAll('.view-nav button').forEach((b) => { const on = b.dataset.view === v; b.classList.toggle('on', on); b.setAttribute('aria-current', on); });
  if (v === 'detail' || v === 'ask') showTab(v === 'ask' ? 'ask-pane' : 'detail');
}
document.querySelectorAll('.view-nav button').forEach((b) => b.addEventListener('click', () => setView(b.dataset.view)));
phone.addEventListener('change', () => { if (!phone.matches && $('main').dataset.view === 'map') setView('brief'); });
setView(phone.matches ? 'map' : 'brief');
$('map-peek').addEventListener('click', () => setView('detail'));
$('legend-toggle').addEventListener('click', (e) => e.currentTarget.setAttribute('aria-expanded', $('map-top').classList.toggle('legend-open')));
$('legend-toggle').setAttribute('aria-expanded', $('map-top').classList.toggle('legend-open', !phone.matches && innerHeight > 560));

// Gemma answers in Markdown: escape first, then allow bold / italic / code, lists, headings, paragraphs
function md(text) {
  const inline = (t) => t.replace(/\*\*(.+?)\*\*/g, '<b>$1</b>').replace(/(^|[^*])\*(?!\s)([^*]+?)\*(?!\*)/g, '$1<i>$2</i>').replace(/`([^`]+)`/g, '<code>$1</code>');
  let html = '', list = null;
  const close = () => { if (list) { html += `</${list}>`; list = null; } };
  for (const raw of esc(text).split('\n')) {
    const l = raw.trim(); let m;
    if ((m = l.match(/^\d+[.)]\s+(.*)/))) { if (list !== 'ol') { close(); html += '<ol>'; list = 'ol'; } html += `<li>${inline(m[1])}</li>`; }
    else if ((m = l.match(/^[-*•]\s+(.*)/))) { if (list !== 'ul') { close(); html += '<ul>'; list = 'ul'; } html += `<li>${inline(m[1])}</li>`; }
    else if ((m = l.match(/^#{1,4}\s+(.*)/))) { close(); html += `<h4>${inline(m[1])}</h4>`; }
    else if (!l) close();
    else { close(); html += `<p>${inline(l)}</p>`; }
  }
  close();
  return html;
}
function addMsg(cls, html) {
  const el = document.createElement('div'); el.className = `msg ${cls}`; el.innerHTML = html;
  $('chat-log').appendChild(el); $('chat-log').scrollTop = $('chat-log').scrollHeight;
  return el;
}

$('ask-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const q = $('ask-input').value.trim(); if (!q) return;
  setView('ask');
  const btn = $('ask-btn'); btn.disabled = true; $('ask-input').value = '';
  addMsg('q', esc(q));
  const a = addMsg('a', '<div class="who">ΣX · Gemma 4</div><div class="thinking">Reading the evidence</div>');
  try {
    const zone = SUGGESTED.includes(q) ? null : state.selected;
    const r = await fetch('/api/ask', { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ question: q, zone }) });
    const j = await r.json();
    a.innerHTML = `<div class="who">ΣX · ${esc(j.model || 'Gemma 4')}${j.cached ? ' · cached' : ''}</div><div class="md">${md(j.answer || j.error || j.message || 'No answer.')}</div>`;
  } catch (err) { a.innerHTML = '<div class="who">ΣX</div><div class="md"><p>Could not reach Gemma. Try again.</p></div>'; }
  $('chat-log').scrollTop = $('chat-log').scrollHeight;
  btn.disabled = false; if (!touch.matches) $('ask-input').focus();
});

// theme: follows the OS until the viewer picks one, then remembers it
const theme = () => document.documentElement.dataset.theme === 'light' ? 'light' : 'dark';
const savedTheme = () => { try { return localStorage.getItem('sigmax-theme'); } catch (e) { return null; } };
function syncThemeButton() {
  const next = theme() === 'light' ? 'dark' : 'light';
  $('theme-toggle').innerHTML = next === 'light' ? '☀<span class="lbl"> Light</span>' : '☾<span class="lbl"> Dark</span>';  // phones show the icon only
  $('theme-toggle').title = `Switch to ${next} mode`;
  $('theme-toggle').setAttribute('aria-label', `Switch to ${next} mode`);
}
function setTheme(t) {
  if (t === theme()) return;
  document.documentElement.dataset.theme = t;
  syncThemeButton();
  map?.setStyle(BASEMAP[t], { diff: false });  // full reload so style.load re-adds our layers in the new colours
}
$('theme-toggle').addEventListener('click', () => {
  const t = theme() === 'light' ? 'dark' : 'light';
  try { localStorage.setItem('sigmax-theme', t); } catch (e) {}
  setTheme(t);
});
matchMedia('(prefers-color-scheme: light)').addEventListener('change', (e) => { if (!savedTheme()) setTheme(e.matches ? 'light' : 'dark'); });
syncThemeButton();

boot();
</script>
</body>
</html>
@endverbatim
