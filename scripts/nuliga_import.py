#!/usr/bin/env python3
"""Read only public TVBB nuLiga tournament calendars and publish confirmed competition metadata.

No login, no personal data, no requests that modify nuLiga.
Only explicitly allowlisted Tennis-Schiri events are matched.
"""
import datetime as dt
import json
import re
import sys
import unicodedata
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request, urlopen

ROOT = Path(__file__).resolve().parents[1]
CONFIG = ROOT / "scripts" / "nuliga-events.json"
DEST = ROOT / "public" / "data" / "nuliga-tournaments.json"
BASE_URL = "https://tvbb.liga.nu/cgi-bin/WebObjects/nuLigaTENDE.woa/wa/tournamentCalendar"
DATE_RANGE = re.compile(r"(\d{1,2})\.(\d{1,2})\.\s*(?:bis|[-–])\s*(\d{1,2})\.(\d{1,2})\.", re.I)
COMPETITION = re.compile(r"\b(Einzel|Doppel)\b", re.I)
LK_RANGE = re.compile(r"\d{1,2},\d\s*[–-]\s*\d{1,2},\d")


def cleaned(text):
    return re.sub(r"\s+", " ", text).strip()


def simplified(text):
    value = unicodedata.normalize("NFKD", text.casefold())
    return cleaned(re.sub(r"[^a-z0-9 ]+", " ", "".join(ch for ch in value if not unicodedata.combining(ch))))


class TournamentTable(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.rows = []
        self.row = None
        self.cell = None

    def handle_starttag(self, tag, attrs):
        if tag == "tr":
            self.row = []
        elif tag in ("td", "th") and self.row is not None:
            self.cell = []
        elif tag == "br" and self.cell is not None:
            self.cell.append(" ")

    def handle_data(self, data):
        if self.cell is not None:
            self.cell.append(data)

    def handle_endtag(self, tag):
        if tag in ("td", "th") and self.cell is not None and self.row is not None:
            self.row.append(cleaned(" ".join(self.cell)))
            self.cell = None
        elif tag == "tr" and self.row is not None:
            if self.row:
                self.rows.append(self.row)
            self.row = None


def date_range(label, anchor):
    match = DATE_RANGE.search(label)
    if not match:
        return None
    a_day, a_month, b_day, b_month = map(int, match.groups())
    options = []
    for year in (anchor.year - 1, anchor.year, anchor.year + 1):
        try:
            start = dt.date(year, a_month, a_day)
            end = dt.date(year + (1 if b_month < a_month else 0), b_month, b_day)
        except ValueError:
            continue
        if end < start or (end - start).days > 90:
            continue
        options.append((abs((start - anchor).days), start, end))
    if not options:
        return None
    _, start, end = min(options)
    return (start.isoformat(), end.isoformat())


def competitions_from_cells(cells):
    result = []
    for index, value in enumerate(cells):
        if not COMPETITION.search(value) or len(value) > 140:
            continue
        # Country, tournament heading and other fields are never competitions.
        if DATE_RANGE.search(value):
            continue
        lk = next((m.group(0).replace("-", "–").replace(" ", "")
                   for cell in cells[index + 1:index + 3]
                   if (m := LK_RANGE.search(cell))), None)
        item = {"name": cleaned(value)}
        if lk:
            item["lk"] = lk
        result.append(item)
        break
    return result


def tournaments_from_html(html, anchor):
    parser = TournamentTable()
    parser.feed(html)
    records = []
    current = None
    for cells in parser.rows:
        candidates = [(i, date_range(cell, anchor))
                      for i, cell in enumerate(cells) if DATE_RANGE.search(cell)]
        dated = next(((i, date) for i, date in candidates if date), None)
        if dated:
            if current:
                records.append(current)
            ix, (start, end) = dated
            title = next((s for s in cells[ix + 1:] if s), "")
            current = {"date_start": start, "date_end": end,
                       "official_title": title, "competitions": []} if title else None
            if current:
                current["competitions"].extend(competitions_from_cells(cells[ix + 2:]))
        elif current:
            current["competitions"].extend(competitions_from_cells(cells))
    if current:
        records.append(current)
    return records


def download(week_date):
    url = BASE_URL + "?date=" + week_date + "&federation=TVBB"
    request = Request(url, headers={"User-Agent": "Tennis-Schiri-Turnierdaten/1.0"})
    with urlopen(request, timeout=25) as response:
        if response.status != 200:
            raise RuntimeError("HTTP " + str(response.status))
        charset = response.headers.get_content_charset() or "utf-8"
        html = response.read(2_000_000).decode(charset, errors="replace")
    return url, html


def best_match(source, config):
    day = config["date_start"]
    title = simplified(source["official_title"])
    if source["date_start"] != day:
        return False
    if not any(simplified(alias) in title for alias in config["title_contains"]):
        return False
    club = config.get("club_contains")
    if club and simplified(club) not in title:
        return False
    return True


def main():
    entries = json.loads(CONFIG.read_text(encoding="utf-8"))["events"]
    previous = json.loads(DEST.read_text(encoding="utf-8")) if DEST.exists() else {"events": {}}
    existing = previous.get("events", {})
    results = {}
    fetched = {}
    today = dt.datetime.now(dt.timezone.utc).date().isoformat()
    for config in entries:
        start = dt.date.fromisoformat(config["date_start"])
        # nuLiga calendar covers weeks: Monday is the canonical lookup day.
        week = (start - dt.timedelta(days=start.weekday())).isoformat()
        if week not in fetched:
            try:
                url, html = download(week)
                parsed = tournaments_from_html(html, start)
                fetched[week] = (url, parsed)
                print(f"Kalender {week}: {len(parsed)} Turniereinträge gelesen")
            except Exception as error:
                print(f"::warning::Kalender {week} nicht abrufbar: {type(error).__name__}")
                fetched[week] = (None, [])
        url, candidates = fetched[week]
        matching = [item for item in candidates if best_match(item, config)]
        if not matching:
            print(f"Nicht eindeutig gefunden: {config['id']} – bestehende Daten bleiben erhalten")
            continue
        # Multiple nuLiga rows for the same competition are deduplicated by name + LK.
        labels = {}
        for match in matching:
            for value in match["competitions"]:
                if len(value["name"]) <= 130:
                    labels[(value["name"], value.get("lk", ""))] = value
        if not labels:
            print(f"::warning::{config['id']}: keine auslesbaren Konkurrenzen – nicht übernehmen")
            continue
        record = matching[0]
        results[config["id"]] = {
            "official_title": record["official_title"][:220],
            "date_start": record["date_start"],
            "date_end": record["date_end"],
            "competitions": list(labels.values()),
            "source_url": url,
            "verified_on": today,
        }
        print(f"{config['id']}: {len(labels)} Konkurrenzen übernommen")

    if not results:
        print("::error::Kein Turnier eindeutig zugeordnet: Import abgebrochen")
        return 1
    # Keep older confirmed events where nuLiga has no current data.
    merged = dict(existing)
    for key, item in results.items():
        old = merged.get(key)
        if old and {k: v for k, v in old.items() if k != "verified_on"} == {k: v for k, v in item.items() if k != "verified_on"}:
            item["verified_on"] = old.get("verified_on", today)
        merged[key] = item
    payload = {"source": "TVBB nuLiga public tournament calendar", "events": dict(sorted(merged.items()))}
    if payload != previous:
        DEST.parent.mkdir(parents=True, exist_ok=True)
        DEST.write_text(json.dumps(payload, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
        print(f"Import aktualisiert: {len(results)} Turniere, insgesamt {len(merged)} Datensätze")
    else:
        print("Keine Änderungen seit dem letzten Import")
    if "GITHUB_STEP_SUMMARY" in __import__("os").environ:
        with open(__import__("os").environ["GITHUB_STEP_SUMMARY"], "a", encoding="utf-8") as summary:
            summary.write("## nuLiga-Import (nur lesen)\n\n")
            summary.write(f"**{len(results)}** Turniere bestätigt, **{len(merged)}** Datensätze im Kalender.\n\n")
            for key, value in results.items():
                summary.write(f"- {key}: {len(value['competitions'])} Konkurrenzen\n")
            summary.write("\nVeröffentlichung erfolgt separat über den Website-Upload.\n")
    return 0


if __name__ == "__main__":
    sys.exit(main())
