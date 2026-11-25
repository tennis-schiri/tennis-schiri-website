#!/usr/bin/env python3
"""TENNILO project showcase: independent detail page, real Score assets, honest ClubDisplay preview."""
from pathlib import Path
import hashlib
import re

root = Path(__file__).resolve().parents[1] / "public"
home = (root / "index.html").read_text(encoding="utf-8")
page = (root / "projekte/index.html").read_text(encoding="utf-8")
style = (root / "assets/project-showcase.css").read_text(encoding="utf-8")

for token in (
    'id="tennilo"', 'id="clubdisplay"', 'href="projekte/#score"',
    'href="projekte/#clubdisplay"', 'class="ts-projects-grid"',
    'src="assets/projekte-score-watch.png"',
    'src="assets/projekte-clubdisplay-vorschau.svg"',
):
    assert token in home, f"Startseiten-Teaser fehlt: {token}"
for token in (
    'id="score"', 'id="clubdisplay"', 'id="score-einblicke"',
    'src="../assets/projekte-score-watch.png"',
    'src="../assets/projekte-score-phone.png"',
    'src="../assets/projekte-score-board.png"',
    'src="../assets/projekte-clubdisplay-vorschau.svg"',
    'src="../assets/projekte-clubdisplay-logo.png"',
    'https://tennilo.de/clubdisplay/',
    'https://clubdisplay.tennilo.de/',
    'kein', 'Prototyp',
):
    assert token in page, f"Projektseite unvollständig: {token}"
assert 'keine echte Bildschirmaufnahme' in page.lower(), "Illustration muss gekennzeichnet bleiben"
assert 'project-features' in style and '.ts-projects-grid' in style
assert '<meta name="robots" content="noindex,nofollow,noarchive">' in page

# The three images are exact, byte-for-byte snapshots provided by the TENNILO website.
official_blobs = {
    "projekte-score-watch.png": "9f662c0bc113348d762524eda8044bcdbfe66e26",
    "projekte-score-phone.png": "c5f672b973408481204d17556fcbd43dbeac3b79",
    "projekte-score-board.png": "287fe6070dfb5785ea211772678b74c6f8ef4612",
    "projekte-clubdisplay-logo.png": "2d460f33f2856149b2d70eae4f0a0534e704fea0",
}
for filename, expected in official_blobs.items():
    data = (root / "assets" / filename).read_bytes()
    assert data.startswith(b"\x89PNG\r\n\x1a\n"), f"Ungültige Bilddatei: {filename}"
    blob = hashlib.sha1(b"blob " + str(len(data)).encode() + b"\0" + data).hexdigest()
    assert blob == expected, f"Originalbild verändert: {filename}"

svg = (root / "assets/projekte-clubdisplay-vorschau.svg").read_text(encoding="utf-8")
assert 'KEIN ORIGINAL-SCREENSHOT' in svg
assert 'viewBox="0 0 1600 900"' in svg

# All content pages should expose the project page through header and footer.
pages = sorted(root.rglob("index.html")) + [root / "turniere/termin.html"]
assert len(pages) == 17, f"17 Website-Dokumente erwartet, {len(pages)} gefunden"
for p in pages:
    html = p.read_text(encoding="utf-8")
    links = re.findall(r'<a href="(?:\.\./)?projekte/"[^>]*>Projekte</a>', html)
    if len(links) != 2:
        raise AssertionError(f"Projekte-Link fehlt in Navigation oder Footer: {p}")
print("TENNILO-Projekte: zwei gleichwertige Teaser, Detailseite, Original-Screenshots, korrekt markierte Illustration und 17 Seiten geprüft.")
