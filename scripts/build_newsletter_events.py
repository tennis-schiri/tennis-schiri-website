#!/usr/bin/env python3
"""Build the newsletter event catalog from canonical public website pages.

Runs during every manual live deploy; no duplicate date editing in the admin.
No external modules or network needed.
"""
import argparse
import datetime as dt
import html
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1] / "public"
MONTHS = {
    "januar": 1, "februar": 2, "märz": 3, "maerz": 3, "april": 4,
    "mai": 5, "juni": 6, "juli": 7, "august": 8, "september": 9,
    "oktober": 10, "november": 11, "dezember": 12,
}

def clean(markup: str) -> str:
    return " ".join(html.unescape(re.sub(r"<[^>]*>", " ", markup)).split())

def first(pattern: str, markup: str, flags: int = re.S) -> str:
    match = re.search(pattern, markup, flags)
    if not match:
        return ""
    return clean(match.group(1))

def date_from_aria(aria: str) -> tuple[str, str]:
    """German date aria e.g. 'Dienstag, 15. Juni bis Sonntag, 20. Juni 2027'."""
    parts = re.findall(r"(\d{1,2})\.\s+([A-Za-zÄäÖöÜüß]+)", aria)
    year_match = re.search(r"\b(20\d{2})\b", aria)
    if not parts or not year_match:
        raise ValueError(f"Unbekanntes Datumsformat: {aria}")
    year = int(year_match.group(1))
    parsed = []
    for day, month in parts:
        month_no = MONTHS.get(month.casefold())
        if month_no is None:
            raise ValueError(f"Unbekannter Monat: {month}")
        parsed.append(dt.date(year, month_no, int(day)))
    start, end = parsed[0], parsed[-1]
    if end < start:
        raise ValueError(f"Enddatum vor Startdatum: {aria}")
    if start == end:
        date_label = start.strftime("%d.%m.%Y")
    elif start.month == end.month:
        date_label = f"{start.day:02d}.–{end.strftime('%d.%m.%Y')}"
    else:
        date_label = f"{start.strftime('%d.%m.')}–{end.strftime('%d.%m.%Y')}"
    return start.isoformat(), date_label

def tournament_rows(markup: str) -> list[dict]:
    rows = []
    cards = re.finditer(
        r'<article\s+class="tourney-card[^"]*"\s+id="([^"]+)">(.*?)</article>',
        markup, re.S,
    )
    for match in cards:
        uid, block = match.groups()
        aria = first(r'<div class="datebox[^"]*"\s+aria-label="([^"]+)"', block)
        day, label = date_from_aria(aria)
        title = first(r"<h3[^>]*>(.*?)</h3>", block)
        location = first(r'<div class="where">(.*?)</div>', block)
        button_match = re.search(r'<a\s+class="tournament-cta"\s+href="([^"]+)"', block)
        link = html.unescape(button_match.group(1)) if button_match else ""
        external = bool(link and link.startswith("https://"))
        if not external:
            link = f"https://tennis-schiri.de/turniere/#{uid}"
        if not uid or not title or not location:
            raise ValueError(f"Turnierdatensatz unvollständig: {uid}")
        rows.append({
            "id": uid, "type": "turnier", "title": title,
            "date_start": day, "date": label, "location": location,
            "url": link, "link_label": "Jetzt anmelden" if external else "Turnierdetails ansehen",
        })
    return rows

def workshop_rows(markup: str) -> list[dict]:
    rows = []
    sections = re.finditer(
        r'<section\s+class="workshop-upcoming"\s+id="([^"]+)"[^>]*>(.*?)</section>',
        markup, re.S,
    )
    for match in sections:
        uid, block = match.groups()
        aria = first(r'<div class="workshop-upcoming-date"\s+aria-label="([^"]+)"', block)
        date_start, date_label = date_from_aria(aria)
        title = first(r'<h2[^>]*>(.*?)</h2>', block)
        location = first(r'<p class="workshop-upcoming-location">(.*?)</p>', block)
        location = re.sub(r"^Veranstaltungsort:\s*", "", location)
        if not uid or not title or not location:
            raise ValueError(f"Workshopdatensatz unvollständig: {uid}")
        rows.append({
            "id": uid, "type": "veranstaltung", "title": title,
            "date_start": date_start, "date": date_label, "location": location,
            "url": f"https://tennis-schiri.de/leistungen/#{uid}",
            "link_label": "Workshopdetails ansehen",
        })
    return rows

def build() -> dict:
    tournaments = (ROOT / "turniere" / "index.html").read_text(encoding="utf-8")
    activities = (ROOT / "leistungen" / "index.html").read_text(encoding="utf-8")
    events = tournament_rows(tournaments) + workshop_rows(activities)
    if not events or not any(e["type"] == "turnier" for e in events) or not any(e["type"] == "veranstaltung" for e in events):
        raise ValueError("Turniere oder Veranstaltungen fehlen; Newsletter-Katalog nicht überschreiben.")
    if len({e["id"] for e in events}) != len(events):
        raise ValueError("Doppelte Veranstaltungs-IDs.")
    events.sort(key=lambda e: (e["date_start"], e["type"], e["id"]))
    return {"version": 1, "source": "Tennis-Schiri Turniere und Leistungen", "events": events}

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--output", type=Path, required=True)
    args = parser.parse_args()
    catalog = build()
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(catalog, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Newsletter-Katalog erstellt: {len(catalog['events'])} Termine aus der Website")

if __name__ == "__main__":
    main()
