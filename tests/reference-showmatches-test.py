#!/usr/bin/env python3
"""Check showmatch references against the former tennis-schiri.de reference page."""
from html.parser import HTMLParser
from pathlib import Path
import re

root=Path(__file__).resolve().parents[1]/"public"
page=(root/"referenzen"/"index.html").read_text(encoding="utf-8")
css=(root/"assets"/"site.css").read_text(encoding="utf-8")

expect={
    "Boris Becker": ("Michael Stich","Berlin","25. August 2002","LINIENRICHTER"),
    "Steffi Graf": ("Gabriela Sabatini","Berlin","25. September 2004","LINIENRICHTER"),
    "Ivan Lendl": ("Carl-Uwe Steeb","Seeburg","2011","STUHLSCHIEDSRICHTER"),
}
section=re.search(r'<section class="ref-showmatches"\b.*?</section>',page,re.S)
if not section:
    raise SystemExit("Eigenständige Showmatch-Sektion fehlt")
cards=re.findall(r'<article class="ref-showmatch(?: ref-showmatch--chair)?"\b.*?</article>',section.group(),re.S)
if len(cards)!=len(expect):
    raise SystemExit(f"Erwartet {len(expect)} Showmatch-Referenzen, gefunden {len(cards)}")
for leader,(opponent,place,when,role) in expect.items():
    found=[card for card in cards if leader in card]
    if len(found)!=1:
        raise SystemExit("Showmatch fehlt oder doppelt: "+leader)
    if any(x not in found[0] for x in (opponent,place,when,role)):
        raise SystemExit("Fehlerhafte Begegnung / Rolle / Termin: "+leader)
if "Dazu Einsätze bei Showmatches." in page:
    raise SystemExit("Alter unspezifischer Platzhalter steht noch auf der Seite")
if "site.css?v=20261118-1" not in page:
    raise SystemExit("Referenzen laden nicht die aktualisierten Styles")
for selector in (".ref-showmatches", ".ref-showmatches-grid", ".ref-showmatch--chair",
                 '[data-theme="dark"] .ref-showmatch', "@media(max-width:890px)"):
    if selector not in css:
        raise SystemExit("Showmatch-Styles fehlen: "+selector)

print("Showmatch-Referenzen: 3 Begegnungen, korrekte Rollen und responsive Styles vorhanden.")
