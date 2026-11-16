#!/usr/bin/env python3
"""Regression check for clickable event cards and the shared event detail page."""
from pathlib import Path
import re

root=Path(__file__).resolve().parents[1]
public=root/"public"
calendar=(public/"turniere"/"index.html").read_text(encoding="utf-8")
home=(public/"index.html").read_text(encoding="utf-8")
detail=(public/"turniere"/"termin.html").read_text(encoding="utf-8")
script=(public/"assets"/"tournament-detail.js").read_text(encoding="utf-8")
css=(public/"assets"/"site.css").read_text(encoding="utf-8")
ids=re.findall(r'<article class="tourney-card[^"]*" id="([^"]+)"[^>]*>',calendar)
if not ids or len(set(ids))!=len(ids):
    raise SystemExit(f"Kalenderliste leer oder mit doppelten IDs: {len(ids)}")
for uid in ids:
    if f'href="termin.html?id={uid}"' not in calendar:
        raise SystemExit("Verlinkung in der Turnierliste fehlt: "+uid)
home_ids=re.findall(r'data-event-id="([^"]+)"',home)
for uid in home_ids:
    if uid not in ids or f'href="turniere/termin.html?id={uid}"' not in home:
        raise SystemExit("Startseitenverlinkung fehlt oder verweist auf alten Termin: "+uid)
for required in ["id=\"event-content\"","id=\"event-map\"","id=\"event-load-map\"","id=\"event-download-ics\"","id=\"event-registration\""]:
    if required not in detail:
        raise SystemExit("Baustein auf der Detailseite fehlt: "+required)
for feature in ["new DOMParser()","'./index.html'","maps.google.com/maps?output=embed","BEGIN:VCALENDAR","DTSTART;TZID=Europe/Berlin","calendar.google.com/calendar/render"]:
    if feature not in script:
        raise SystemExit("Funktion der Detailseite fehlt: "+feature)
if ".tennis-quote-line{border:" not in css:
    raise SystemExit("Dezenter Rahmen um Tenniszitate fehlt")
if "tourney-detail-link:after" not in css:
    raise SystemExit("Klickfläche für Termine fehlt")
extra=(public/"assets"/"tournament-detail.css").read_text(encoding="utf-8")
for marker in ['id="event-back"','id="event-competitions-panel"',
               'id="event-competitions-groups"','id="event-workshop-spotlight"',
               'tourney-workshop-price--members','tourney-workshop-price--guests']:
    if marker not in detail:
        raise SystemExit("Neuer Detailseiten-Baustein fehlt: "+marker)
for name in ["ntc-93.png","tc-blau-weiss-ahlbeck.png","tc-rotation-friedrichsfelde.jpg"]:
    image=public/"assets"/"venues"/name
    if not image.is_file() or image.stat().st_size<1000:
        raise SystemExit("Vereinslogo fehlt oder ist leer: "+name)
    if name not in script:
        raise SystemExit("Logo ist in der Detailseiten-Logik nicht eingebunden: "+name)
for marker in ["history.back()","renderCompetitions(official)","'is-workshop-detail'","'event-workshop-spotlight'"]:
    if marker not in script:
        raise SystemExit("Verhalten der Detailseite fehlt: "+marker)
for marker in [".tourney-workshop-prices",".tourney-workshop-price--members",
               ".tourney-detail-back",".tourney-competitions-groups"]:
    if marker not in extra:
        raise SystemExit("Gestaltung der Detailseite fehlt: "+marker)
print(f"Turnierdetails: {len(ids)} Termine verlinkt; Original-Vereinslogos, Zurück, Workshop-Preise und Konkurrenzen vorhanden.")
