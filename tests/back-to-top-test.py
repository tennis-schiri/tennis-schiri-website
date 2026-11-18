#!/usr/bin/env python3
"""Check animated, accessible scroll-to-top on all public HTML pages."""
from pathlib import Path
import re

root=Path(__file__).resolve().parents[1]/"public"
pages=[p for p in root.rglob("*.html") if p.name in ("index.html","termin.html")]
if len(pages)<16:
    raise SystemExit("Es fehlen erwartete Seiten im Website-Check")
for page in pages:
    text=page.read_text(encoding="utf-8")
    if not re.search(r'<script src="(?:\.\./)?assets/back-to-top\.js\?v=5" defer></script>',text):
        raise SystemExit(f"Animation fehlt oder alte Skriptversion: {page}")
    # Newer page layouts may use a newer CSS cache revision without breaking the button.
    # Animation rules are checked against the shared stylesheet below.
    if not re.search(r'<link rel="stylesheet" href="(?:\.\./)?assets/site\.css\?v=[A-Za-z0-9._-]+">',text):
        raise SystemExit(f"Gemeinsames Animations-CSS fehlt oder Cache-Version ungueltig: {page}")

css=(root/"assets/site.css").read_text(encoding="utf-8")
script=(root/"assets/back-to-top.js").read_text(encoding="utf-8")
for required in [
    ".back-to-top.is-visible::before",
    "@keyframes ts-back-to-top-halo",
    "@keyframes ts-back-to-top-float",
    "@keyframes ts-back-to-top-launch",
    "prefers-reduced-motion:reduce",
    ".back-to-top.is-visible",
]:
    if required not in css:
        raise SystemExit("Button-Animation fehlt: "+required)
for required in [
    'button.classList.toggle("is-visible", visible)',
    "window.scrollTo({ top: 0,",
    "window.requestAnimationFrame(updateVisibility)",
    "Math.max(260, Math.round(window.innerHeight * 0.35))",
]:
    if required not in script:
        raise SystemExit("Button-Funktion fehlt: "+required)
print(f"Nach-oben-Button: {len(pages)} Seiten, Animation und Reduced-Motion geprueft.")
