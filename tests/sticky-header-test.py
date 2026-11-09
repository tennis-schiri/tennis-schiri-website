#!/usr/bin/env python3
"""Verify fixed navigation and reliable anchor offsets across public pages."""
from pathlib import Path
import re

ROOT=Path(__file__).resolve().parents[1] / "public"
css=(ROOT/"assets"/"site.css").read_text(encoding="utf-8")
matches=list(re.finditer(r"\.site-head\s*\{([^{}]*)\}",css))
if not matches:
    raise SystemExit("Keine Menüleiste in der CSS-Datei definiert")
fixed_rules=[m.group(1) for m in matches if "position:fixed" in m.group(1)]
if not fixed_rules:
    raise SystemExit("Menüleiste ist nicht fixiert")
for prop in ["position:fixed","top:0","z-index:800","background:rgba"]:
    if not any(prop in rule for rule in fixed_rules):
        raise SystemExit("Fixierte Menüleiste fehlerhaft: "+prop)
if "scroll-padding-top:112px" not in css or "scroll-padding-top:93px" not in css:
    raise SystemExit("Sprungmarken könnten hinter der fixierten Menüleiste verschwinden")
if "max-height:calc(100dvh - 93px)" not in css:
    raise SystemExit("Mobiles Klappmenü muss bei kleinen Displays scrollbar bleiben")
pages=sorted(ROOT.rglob("index.html"))+[ROOT/"turniere"/"termin.html"]
if len(pages)!=16:
    raise SystemExit(f"Erwartet 16 Website-Dokumente, gefunden: {len(pages)}")
for page in pages:
    html=page.read_text(encoding="utf-8")
    if '<header class="site-head"' not in html:
        raise SystemExit("Website-Kopf fehlt: "+str(page))
    if 'site.css?v=20261109-2' not in html:
        raise SystemExit("Alte CSS-Cacheversion: "+str(page))
    if 'id="menu-toggle"' not in html or 'id="site-nav"' not in html:
        raise SystemExit("Mobiles Menü fehlt: "+str(page))
print("Sticky Navigation: Desktop, Mobilmenü, Sprungmarken und 16 Seiten geprüft.")
