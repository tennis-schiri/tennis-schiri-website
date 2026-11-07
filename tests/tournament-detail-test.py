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
print(f"Turnierdetails: {len(ids)} Termine verlinkt; Karte, .ics und Kartenrahmen vorhanden.")
