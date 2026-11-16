#!/usr/bin/env python3
"""Validate user-approved 2027 Ahlbeck competition import and official source."""
from pathlib import Path
import json

root=Path(__file__).resolve().parents[1]/"public"
data=json.loads((root/"data/nuliga-tournaments.json").read_text(encoding="utf-8"))
events=data["events"]
assert "turnier-neuenhagen-mixed-2027" in events, "Other confirmed events must remain"
record=events["turnier-ahlbeck-2027"]
assert record["date_start"]=="2027-08-19"
assert record["date_end"]=="2027-08-22"
assert record["source_url"]=="https://tmv.liga.nu/cgi-bin/WebObjects/nuLigaTENDE.woa/wa/tournamentCalendar?date=2027-08-16&federation=TMV"
names=[x["name"] for x in record["competitions"]]
assert len(names)==20 and len(set(names))==20, "Exactly 20 distinct official competitions"
assert sum("Einzel" in x for x in names)==15
assert sum("Doppel" in x for x in names)==5
assert "Damen 70 Einzel (S-4)" in names
assert "Herren 75 Einzel (S-4)" in names
assert "Senioren Mixed 30 Doppel (-)" in names
for relative in ["index.html","turniere/index.html"]:
    page=(root/relative).read_text(encoding="utf-8")
    assert "20 bestätigte Konkurrenzen" in page, relative
    assert "konkreten Altersklassen und Meldefristen bitte" not in page, relative
js=(root/"assets/nuliga-tournaments.js").read_text(encoding="utf-8")
assert '"tmv.liga.nu"' in js and 'TMV-Kalender' in js
print("Ahlbeck 2027: 15 single and 5 double competitions confirmed from TMV; TVBB record preserved.")
