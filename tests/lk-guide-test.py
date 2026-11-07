#!/usr/bin/env python3
"""Check the first LK-tournament guide, the source links, and both entry points."""
from pathlib import Path
import re

ROOT=Path(__file__).resolve().parents[1]/"public"
guide=(ROOT/"lk-turnier"/"index.html").read_text(encoding="utf-8")
home=(ROOT/"index.html").read_text(encoding="utf-8")
turniere=(ROOT/"turniere"/"index.html").read_text(encoding="utf-8")
css=(ROOT/"assets"/"lk-guide.css").read_text(encoding="utf-8")
js=(ROOT/"assets"/"lk-guide.js").read_text(encoding="utf-8")

expected=["verein","dtb-id","lk","account","lizenz","turnier","anmelden","spieltag"]
steps=re.findall(r'<input type="checkbox" class="lk-check" data-id="([^"]+)">',guide)
if steps!=expected:
    raise SystemExit(f"LK-Anleitung: acht Schritte in falscher Reihenfolge: {steps}")
for item in expected:
    if f'id="schritt-{item}"' not in guide:
        raise SystemExit("LK-Anleitung: fehlender Sprungpunkt "+item)
for part in ["<progress id=\"lk-progress\"","id=\"lk-reset\"","id=\"lk-progress-status\"","https://account.tennis.de/web/konten/home","lizenz-fuer-ranglisten-und-lk-spieler.html","anmeldungen-zu-turnieren-und-veranstaltungen.html"]:
    if part not in guide:
        raise SystemExit("LK-Anleitung: fehlende Funktion/Quelle "+part)
if "href=\"lk-turnier/\"" not in home or "href=\"../lk-turnier/\"" not in turniere:
    raise SystemExit("LK-Anleitung: Verlinkung aus Startseite oder Kalender fehlt")
if "localStorage" not in js or "try{" not in js or ".lk-step.is-complete" not in css:
    raise SystemExit("LK-Anleitung: Fortschrittsanzeige nicht vollständig")
if 'name="robots" content="noindex,nofollow,noarchive"' not in guide:
    raise SystemExit("LK-Anleitung: Staging-Schutz fehlt")
if guide.count('ts-logo-dunkel.png?v=20261107-3')!=2:
    raise SystemExit("LK-Anleitung: Header/Footer-Branding unvollständig")
print("LK-Einstieg: acht Schritte, lokale Checkliste, Verweise und beide Einstiege geprüft.")
