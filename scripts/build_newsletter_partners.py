#!/usr/bin/env python3
"""Build the public newsletter partner catalog from tennis-schiri.de partner cards.

Run as part of manual deployments; changes to logo/name/link come from the website.
TENNILO is the separately selectable in-house project with its verified original logo.
"""
import argparse
import html
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1] / "public"
PARTNER_PAGE = ROOT / "partner" / "index.html"

def strip_tags(value: str) -> str:
    return " ".join(html.unescape(re.sub(r"<[^>]+>", " ", value)).split())

def attr(tag: str, name: str) -> str:
    found = re.search(r'\b' + re.escape(name) + r'="([^"]+)"', tag)
    return html.unescape(found.group(1)) if found else ""

def get_partners() -> list[dict]:
    markup = PARTNER_PAGE.read_text(encoding="utf-8")
    results = []
    for match in re.finditer(r'<a\s+class="partner-card\b[^>]*>.*?</a>', markup, re.S):
        block = match.group()
        a = re.search(r"<a\b[^>]*>", block, re.S)
        image = re.search(r"<img\b[^>]*>", block, re.S)
        heading = re.search(r"<h3[^>]*>(.*?)</h3>", block, re.S)
        if not a or not image or not heading:
            raise ValueError("Partnerkarte enthält keinen Link, kein Logo oder keinen Namen.")
        link = attr(a.group(), "href")
        src = attr(image.group(), "src")
        title = strip_tags(heading.group(1))
        if not re.fullmatch(r"https://[^\s<>\"']+", link):
            raise ValueError(f"Ungültige Partneradresse: {title}")
        if not src.startswith("../assets/partner/") or ".." in src[len("../assets/"):]:
            raise ValueError(f"Partnerlogo außerhalb des geprüften Asset-Verzeichnisses: {src}")
        rel = src.removeprefix("../")
        if not (ROOT / rel).is_file():
            raise ValueError(f"Partnerlogo fehlt: {rel}")
        uid = "partner-" + Path(rel).stem
        results.append({
            "id": uid,
            "category": "partner",
            "name": title,
            "url": link,
            "logo": "https://tennis-schiri.de/" + rel,
            "caption": "Partner von Tennis-Schiri",
        })
    if not results:
        raise ValueError("Keine Partner auf der Website gefunden; Katalog wird nicht überschrieben.")
    return results

def build() -> dict:
    rows = get_partners()
    rows.append({
        "id": "tennilo",
        "category": "projekt",
        "name": "TENNILO",
        "url": "https://tennilo.de/",
        "logo": "https://tennilo.de/assets/tennilo-logo.png",
        "caption": "Turnier-App für Vereine · in Entwicklung",
    })
    if len({p["id"] for p in rows}) != len(rows):
        raise ValueError("Doppelte Partner-IDs")
    return {
        "version": 1,
        "source": "Öffentliche Tennis-Schiri-Partnerseite + TENNILO",
        "partners": rows,
    }

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--output", type=Path, required=True)
    args = parser.parse_args()
    catalog = build()
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(catalog, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Newsletter-Banner-Katalog erzeugt: {len(catalog['partners'])} Partner/Projekte")

if __name__ == "__main__":
    main()
