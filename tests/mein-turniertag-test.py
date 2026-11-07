#!/usr/bin/env python3
"""Regression checks: Mein Turniertag – personal preparation without invented assignments."""
from pathlib import Path
import re
root=Path(__file__).resolve().parents[1]/"public"
page=(root/"mein-turniertag"/"index.html").read_text(encoding="utf-8")
script=(root/"assets"/"mein-turniertag.js").read_text(encoding="utf-8")
css=(root/"assets"/"mein-turniertag.css").read_text(encoding="utf-8")
home=(root/"index.html").read_text(encoding="utf-8")
turniere=(root/"turniere"/"index.html").read_text(encoding="utf-8")
guide=(root/"lk-turnier"/"index.html").read_text(encoding="utf-8")
detail=(root/"turniere"/"termin.html").read_text(encoding="utf-8")
detail_js=(root/"assets"/"tournament-detail.js").read_text(encoding="utf-8")
privacy=(root/"datenschutz"/"index.html").read_text(encoding="utf-8")
ids=re.findall(r'class="mt-check" type="checkbox" data-id="([^"]+)"',page)
if len(ids)!=15 or len(set(ids))!=15:
    raise SystemExit(f"Mein Turniertag: erwartet 15 verschiedene Aufgaben; gefunden {len(ids)}")
if len(re.findall(r'class="mt-check-section"',page))!=4:
    raise SystemExit("Mein Turniertag: vier Phasen fehlen")
for item in ["mt-select","mt-arrival","mt-match","mt-notes","mt-progress","mt-count","mt-print","mt-reset","mt-event","mt-event-detail","mt-event-route","mt-data-status"]:
    if f'id="{item}"' not in page:
        raise SystemExit("Mein Turniertag: Bedienelement fehlt: "+item)
if 'site.css?v=' not in page or 'mein-turniertag.css?v=1' not in page or 'mein-turniertag.js?v=1' not in page:
    raise SystemExit("Mein Turniertag: Styles oder Skript nicht eingebunden")
for snippet in ["ts-mein-turniertag-v1","../turniere/index.html","DOMParser","localStorage","window.confirm","replaceState","cache:'no-store'"]:
    if snippet not in script:
        raise SystemExit("Mein Turniertag: Funktion fehlt: "+snippet)
if "credentials:'omit'" not in script:
    raise SystemExit("Mein Turniertag: nur öffentliche Kalenderdaten laden")
if "document.cookie" in script or "fetch('https://" in script:
    raise SystemExit("Mein Turniertag: unbekannte Cookies oder externe Requests")
if "@media print" not in css or ".mt-check-item" not in css:
    raise SystemExit("Mein Turniertag: Druckansicht/Checkliste fehlt")
for name,source,target in [
    ("Startseite",home,'href="mein-turniertag/"'),
    ("Turnierkalender",turniere,'href="../mein-turniertag/"'),
    ("LK-Einsteigerleitfaden",guide,'href="../mein-turniertag/"'),
    ("Turnierdetail",detail,'id="event-my-day"')]:
    if target not in source:
        raise SystemExit("Mein Turniertag: Link aus "+name+" fehlt")
if "myDay.href='../mein-turniertag/?turnier='" not in detail_js:
    raise SystemExit("Mein Turniertag: Event-ID wird aus Turnierdetail nicht übernommen")
if "ts-mein-turniertag-v1" not in privacy:
    raise SystemExit("Mein Turniertag: Datenschutzhinweis zu lokaler Speicherung fehlt")
if page.count('ts-logo-dunkel.png?v=20261107-3')!=2:
    raise SystemExit("Mein Turniertag: fehlende TS-Wortmarke im Header/Footer")
print("Mein Turniertag: 15 Aufgaben, 4 Phasen, Terminwahl, Datenminimierung und Verlinkungen geprüft.")
