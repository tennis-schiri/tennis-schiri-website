#!/usr/bin/env python3
"""Read only the 2027 TMV nuLiga calendar for Ahlbeck; never import data."""
import datetime as dt
import os
import sys
from urllib.request import Request, urlopen

from nuliga_import import simplified, tournaments_from_html

DATE = dt.date(2027, 8, 19)
WEEK = DATE - dt.timedelta(days=DATE.weekday())
URL = ("https://tmv.liga.nu/cgi-bin/WebObjects/nuLigaTENDE.woa/wa/"
       f"tournamentCalendar?date={WEEK.isoformat()}&federation=TMV")


def main():
    print("TMV nuLiga: öffentliche Turnier-Vorschau für 19.–22.08.2027")
    print("Quelle:", URL)
    try:
        with urlopen(Request(URL, headers={
            "User-Agent": "Tennis-Schiri-Readonly-Preview/1.0",
        }), timeout=28) as response:
            if response.status != 200:
                raise RuntimeError("HTTP " + str(response.status))
            charset = response.headers.get_content_charset() or "utf-8"
            raw = response.read(2_000_000).decode(charset, errors="replace")
        records = tournaments_from_html(raw, DATE)
    except Exception as error:
        print(f"::warning::TMV-Kalender nicht abrufbar: {type(error).__name__}. Nichts übernommen.")
        return 0

    print(f"Einträge aus dem Kalender: {len(records)}")
    candidates = [
        r for r in records
        if r["date_start"] == DATE.isoformat()
        and ("krohn" in simplified(r["official_title"]) or
             "ahlbeck" in simplified(r["official_title"]))
    ]
    if not candidates:
        print("::warning::Kein eindeutig passender Ahlbeck-Eintrag vom 19.08.2027.")
        print("Keine Altersklassen für 2027 bestätigt; 2026-Daten dürfen nicht übernommen werden.")
        return 0

    names = sorted({comp["name"] for r in candidates for comp in r["competitions"]})
    print("Amtlicher Eintrag:", candidates[0]["official_title"][:220])
    print("Eindeutige Konkurrenzen:", len(names))
    if not names:
        print("::warning::Turnier gefunden, aber die Konkurrenzliste ist nicht auslesbar.")
        return 0
    for name in names:
        print("  -", name)

    if os.environ.get("GITHUB_STEP_SUMMARY"):
        with open(os.environ["GITHUB_STEP_SUMMARY"], "a", encoding="utf-8") as summary:
            summary.write("## Ahlbeck 2027: TMV-nuLiga-Vorschau (keine Übernahme)\n\n")
            summary.write(f"Quelle: {URL}\n\n")
            summary.write(f"**{len(names)} auslesbare Konkurrenzen**\n\n")
            for name in names:
                summary.write("- " + name.replace("<", "&lt;").replace(">", "&gt;") + "\n")
    print("VORSCHAU: Keine Dateien geändert, kein Import vorgenommen.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
