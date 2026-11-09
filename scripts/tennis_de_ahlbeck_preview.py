#!/usr/bin/env python3
"""Read-only, public-browser inspection of a specifically approved tennis.de tournament.

This diagnostic never logs credentials, cookies, lists of registrants or raw HTML.
It does not submit forms or write to tennis.de.
"""
import re
import sys
import time
from urllib.parse import urlparse

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options

URL = "https://www.tennis.de/spielen/spielbetrieb/turniersuche.html#detail/870524"
SAFE_TERMS = re.compile(
    r"turnier|krohn|ahlbeck|2027|august|veranstalt|austrag|kategor|ranglist|"
    r"meldung|meldeschluss|nennschluss|auslosung|wettbewerb|konkurrenz|"
    r"altersklass|senior|damen|herren|doppel|mixed|spielbeginn|nenngeld|"
    r"startgeld|belag|platzanlage|ort\b|b[aä]lle|kontakt", re.I)
BLOCKED_TERMS = re.compile(
    r"meldeliste|teilnehmerliste|zulassungsliste|tableau|spielerliste|"
    r"spielerprofil|registrierung|angemeldete spieler", re.I)


def extract(text):
    lines = [re.sub(r"\s+", " ", x).strip() for x in text.splitlines()]
    lines = [x for x in lines if x and len(x) < 240]
    result = []
    seen = set()
    for i, line in enumerate(lines):
        if BLOCKED_TERMS.search(line):
            continue
        if SAFE_TERMS.search(line):
            for candidate in lines[max(0, i - 1):min(len(lines), i + 3)]:
                if (candidate in seen or BLOCKED_TERMS.search(candidate)
                        or "@" in candidate or len(candidate) > 200):
                    continue
                # Skip long digit sequences and likely telephone numbers.
                if re.search(r"(?:\+?\d[\d -]{8,}\d)", candidate):
                    continue
                seen.add(candidate)
                result.append(candidate)
                if len(result) >= 100:
                    break
        if len(result) >= 100:
            break
    return result


def main():
    parsed = urlparse(URL)
    if (parsed.scheme, parsed.netloc, parsed.path, parsed.fragment) != (
        "https", "www.tennis.de", "/spielen/spielbetrieb/turniersuche.html",
        "detail/870524"
    ):
        raise ValueError("Unzulässige URL")
    options = Options()
    options.add_argument("--headless=new")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--disable-extensions")
    options.add_argument("--window-size=1440,1080")
    options.add_argument("--lang=de-DE")
    driver = webdriver.Chrome(options=options)
    try:
        driver.set_page_load_timeout(45)
        driver.get(URL)
        deadline = time.monotonic() + 30
        best = ""
        while time.monotonic() < deadline:
            text = driver.execute_script("return document.body ? document.body.innerText : ''")
            if len(text) > len(best):
                best = text
            # A real tournament detail must contain its name; the general
            # search shell alone is not enough for a successful inspection.
            if re.search(r"manfred.{0,15}krohn", text, re.I) and len(text) > 600:
                best = text
                break
            time.sleep(2)
        print("## tennis.de: öffentliche Vorschau 870524")
        print("Webadresse:", URL)
        print("Geladene sichtbare Zeichen:", len(best))
        print("Turnierdetail erkannt:", bool(re.search(r"manfred.{0,15}krohn", best, re.I)))
        items = extract(best)
        for item in items:
            print("•", item)
        if not re.search(r"manfred.{0,15}krohn", best, re.I):
            print("::warning::Nur das Suchformular, nicht die eigentlichen Turnierdetails sichtbar. "
                  "Die Seite erfordert möglicherweise einen anderen Zugriff.")
        with open(__import__("os").environ.get("GITHUB_STEP_SUMMARY", "/dev/null"),
                  "a", encoding="utf-8") as summary:
            summary.write("## tennis.de – öffentliche Ahlbeck-Vorschau\\n\\n")
            summary.write("Turnier-ID 870524 · nur gelesen · keine Anmeldung und keine Übernahme\\n\\n")
            summary.write("Turnierdetail erkennbar: **" +
                          ("ja" if re.search(r"manfred.{0,15}krohn", best, re.I) else "nein") +
                          "**\\n\\n")
            for item in items:
                summary.write("- " + item.replace("<", "&lt;").replace(">", "&gt;") + "\\n")
        return 0
    finally:
        driver.quit()


if __name__ == "__main__":
    sys.exit(main())
