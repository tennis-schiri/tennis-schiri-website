#!/usr/bin/env python3
"""Static regression checks for the deployed TS-09c brand files and PHP mail template."""
from pathlib import Path
import re
import struct

ROOT=Path(__file__).resolve().parents[1]
PUB=ROOT/"public"
pages=sorted(PUB.rglob("index.html"))
if len(pages)!=13:
    raise SystemExit(f"Erwartet 13 Inhaltsseiten, gefunden: {len(pages)}")

for html in pages:
    data=html.read_text(encoding="utf-8")
    prefix="" if html==PUB/"index.html" else "../"
    expected=f'<img src="{prefix}assets/branding/ts-logo-dunkel.png?v=20261107-3"'
    if data.count(expected)!=2:
        raise SystemExit(f"Header/Footer ohne freigegebenes Logo: {html}")
    for required in [
        '<link rel="icon" type="image/png" href="/assets/branding/ts-favicon.png">',
        '<meta property="og:image" content="https://tennis-schiri.de/assets/branding/ts-logo-ball.png">',
    ]:
        if required not in data:
            raise SystemExit(f"Veralteter Browser-/Social-Branding-Verweis: {html} -> {required}")
    css_prefix=f'{prefix}assets/site.css'
    if not re.search(r'<link\\s+rel="stylesheet"\\s+href="'+re.escape(css_prefix)+r'\\?v=[A-Za-z0-9._-]+">',data):
        raise SystemExit(f"CSS-Stylesheet fehlt oder ist falsch eingebunden: {html}")
    if '<span class="mark"' in data:
        raise SystemExit(f"Veraltetes CSS-Logo noch vorhanden: {html}")

for name in [
    "ts-logo-hell.png","ts-logo-dunkel.png","ts-logo-ball.png",
    "ts-logo-claim.png","ts-kurzlogo.png","ts-favicon.png",
    "ts-tennisball.png","ts-wortmarke.png",
]:
    path=PUB/"assets"/"branding"/name
    if not path.is_file():
        raise SystemExit(f"Branding-Original fehlt: {name}")
    with path.open("rb") as fd:
        hdr=fd.read(24)
    if not hdr.startswith(b"\x89PNG\r\n\x1a\n"):
        raise SystemExit(f"Ungültiger PNG-Header: {name}")
    width,height=struct.unpack(">II",hdr[16:24])
    if min(width,height)<64 or max(width,height)>4000:
        raise SystemExit(f"Ungültige Bildgröße für {name}: {width}x{height}")

svg=(PUB/"favicon.svg").read_text(encoding="utf-8")
if 'data:image/png;base64,' not in svg:
    raise SystemExit("Legacy-Favicon verweist noch auf die alte Zeichenlogo-Version")

email=(PUB/"newsletter-template.php").read_text(encoding="utf-8")
if 'https://tennis-schiri.de/assets/branding/ts-logo-dunkel.png?v=20261107-3' not in email:
    raise SystemExit("HTML-Newsletter benutzt noch altes Textlogo")
for name in ["newsletter-admin.php","newsletter-mail.php","newsletter.php","newsletter-setup.php"]:
    if "/assets/branding/ts-logo-" not in (PUB/name).read_text(encoding="utf-8"):
        raise SystemExit(f"Newsletter-Oberfläche ohne neues Logo: {name}")

print("TS-09c-Logo in 13 Seiten, Newsletter, Browser und Social-Vorschau geprüft.")
