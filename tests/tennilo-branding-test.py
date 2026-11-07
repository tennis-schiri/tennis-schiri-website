#!/usr/bin/env python3
"""Verify the original TENNILO artwork is locally hosted and used in suitable places."""
from pathlib import Path
import hashlib
import struct

public = Path(__file__).resolve().parents[1] / "public"
assets = public / "assets"
logos = {
    "tennilo-logo.png": ("032347385953a24aa3cc77c944c8c1c0d51459a9", (4000, 723)),
    "tennilo-icon.png": ("28ff5eec0e6cdbf890c727c47c1b496deb68919c", (2048, 2048)),
}
for name, (expected_hash, expected_size) in logos.items():
    data = (assets / name).read_bytes()
    if not data.startswith(b"\x89PNG\r\n\x1a\n"):
        raise SystemExit("TENNILO asset is not a PNG: " + name)
    if struct.unpack(">II", data[16:24]) != expected_size:
        raise SystemExit("Unexpected TENNILO logo dimensions: " + name)
    git_blob = hashlib.sha1(b"blob " + str(len(data)).encode() + b"\0" + data).hexdigest()
    if git_blob != expected_hash:
        raise SystemExit("TENNILO artwork differs from original tennilo-website: " + name)

homepage = (public / "index.html").read_text(encoding="utf-8")
css = (assets / "site.css").read_text(encoding="utf-8")
if homepage.count('src="assets/tennilo-logo.png"') != 2:
    raise SystemExit("TENNILO wordmark must appear in project teaser and Instagram heading")
if homepage.count('src="assets/tennilo-icon.png"') != 1:
    raise SystemExit("TENNILO icon must appear in the Instagram profile header")
if 'href="https://tennilo.de/"' not in homepage:
    raise SystemExit("TENNILO project must link to official domain")
if 'data-instagram-account="tennilo"' not in homepage:
    raise SystemExit("TENNILO Instagram feed removed")
if 'site.css?v=20261108-2' not in homepage:
    raise SystemExit("Homepage needs fresh style version")
if ".tennilo-brand-card" not in css or ".ig-tennilo-wordmark" not in css:
    raise SystemExit("TENNILO logo styling missing")
if 'background:url("tennilo-icon.png")' in css:
    raise SystemExit("TENNILO 2048px icon must not load on every footer")
print("Original TENNILO wordmark and app icon verified; homepage placements and links OK.")
