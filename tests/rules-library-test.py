#!/usr/bin/env python3
"""Verify the public rules library and the homepage rule teaser."""
from pathlib import Path
import re
root=Path(__file__).resolve().parents[1]/"public"
page=(root/"regelwissen"/"index.html").read_text(encoding="utf-8")
home=(root/"index.html").read_text(encoding="utf-8")
css=(root/"assets"/"rules-library.css").read_text(encoding="utf-8")
js=(root/"assets"/"rules-library.js").read_text(encoding="utf-8")
cases=re.findall(r'<details class="rules-case" id="([^"]+)" data-category="([^"]+)">',page)
if len(cases)<9 or len(set(uid for uid,_ in cases))!=len(cases):
    raise SystemExit(f"Regelwissen: erwartet mindestens neun eindeutige Fälle; gefunden {len(cases)}")
if set(category for _,category in cases)!={"aufschlag","ball","doppel"}:
    raise SystemExit("Regelwissen: fehlende Kategorie")
for case,_ in cases:
    if f'id="{case}"' not in page:
        raise SystemExit(f"Regelwissen: Fall {case} fehlt")
for marker in ['id="rules-grid"','id="rules-search"','id="rules-count"','id="regelwerk"','ITF Regel']:
    if marker not in page:
        raise SystemExit("Regelwissen: Element fehlt: "+marker)
if "Tennisregeln%20der%20ITF.pdf.coredownload.pdf" not in page:
    raise SystemExit("Regelwissen: offizieller Regelverweis fehlt")
if 'class="rules-home-answer"' not in home or 'href="regelwissen/#linienball"' not in home:
    raise SystemExit("Regelwissen: Startseitenfrage fehlt")
if "Coming soon" in page or "Regelwissen.<br>Coming soon." in home:
    raise SystemExit("Regelwissen: Vorschautexte nicht vollständig entfernt")
if ".rules-case-answer" not in css or "data.category" not in js:
    raise SystemExit("Regelwissen: Interaktivität unvollständig")
print(f"Regelwissen: {len(cases)} Regelfälle und Startseitenfrage geprüft.")
