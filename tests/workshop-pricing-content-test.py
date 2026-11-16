#!/usr/bin/env python3
"""Keep workshop public copy, signup and official TVBB download link in sync."""
from pathlib import Path

root=Path(__file__).resolve().parents[1]/"public"
pages={
    "index.html": (root/"index.html").read_text(encoding="utf-8"),
    "leistungen/index.html": (root/"leistungen/index.html").read_text(encoding="utf-8"),
    "turniere/index.html": (root/"turniere/index.html").read_text(encoding="utf-8"),
    "turniere/termin.html": (root/"turniere/termin.html").read_text(encoding="utf-8"),
}

for name, content in pages.items():
    assert "31.01.2027" in content, f"Frühbucherfrist fehlt in {name}"
    assert "15 €" in content and "25 €" in content, f"Beide Tarifstufen fehlen in {name}"
    assert "20 €" not in content, f"Veralteter Pauschalpreis auf {name}"
    assert "Familienangehörig" in content, f"Familienfreistellung fehlt in {name}"
for name in ("index.html","leistungen/index.html"):
    content=pages[name]
    assert "name=\"member\"" in content, f"Kostenlos-Checkbox fehlt auf {name}"
    assert "Elternteil bzw. Familienangehöriger" in content, f"Anmeldung deckt Familien nicht ab: {name}"
    assert "Vor Ort (15 € bis 31.01.2027, danach 25 €)" in content
    assert "PayPal (15 € bis 31.01.2027, danach 25 €)" in content

assert "tourney-workshop-price-later" in pages["turniere/termin.html"]
assert "tournament-detail.css?v=3" in pages["turniere/termin.html"]
lib=(root/"workshop-lib.php").read_text(encoding="utf-8")
handler=(root/"workshop.php").read_text(encoding="utf-8")
assert "workshopGuestFeeCents()" in lib
assert "'2027-01-31'?1500:2500" in lib
assert "new DateTimeZone('Europe/Berlin')" in lib
assert "workshop_registrations.fee_cents ELSE excluded.fee_cents" in handler
assert "workshopFeeLabel($savedFee)" in handler
assert "Familienangehörige" in lib

reference=(root/"assets/rules-reference.js").read_text(encoding="utf-8")
rules=(root/"regelwissen/index.html").read_text(encoding="utf-8")
assert "https://tvbb.de/verband/#downloads" in reference
assert "https://tvbb.de/verband/#downloads" in rules
assert "rules-reference.js?v=2" in rules

print("Workshop-Tarife, Familienregelung und TVBB-Downloads: alle Oberflächen konsistent.")
