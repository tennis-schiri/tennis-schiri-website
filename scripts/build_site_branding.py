#!/usr/bin/env python3
"""Use the approved TS-09c design files.

The original Google Drive logo set is in branding/. The two newer transparent
variants (for dark / light backgrounds) deliberately replace the framed
variants from the archive. All source PNG files are copied byte-for-byte.
"""
import argparse
import hashlib
import base64
import struct
import sys
import zipfile
from pathlib import Path
from fix_favicon import clean_favicon

ROOT=Path(__file__).resolve().parents[1]
SOURCE=ROOT/"branding"/"tennis-schiri-ts09c-logo-set.zip"
ASSETS={
    "01_hauptlogo_hell.png":"ts-logo-hell.png",
    "02_hauptlogo_dunkel.png":"ts-logo-dunkel.png",
    "03_logo_mit_tennisball.png":"ts-logo-ball.png",
    "04_logo_mit_claim.png":"ts-logo-claim.png",
    "05_kurzlogo_TS.png":"ts-kurzlogo.png",
    "06_favicon.png":"ts-favicon.png",
    "07_tennisball_symbol.png":"ts-tennisball.png",
    "08_wortmarke.png":"ts-wortmarke.png",
}

def export(target:Path) -> None:
    if not SOURCE.is_file():
        raise SystemExit(f"Fehlendes finales Logo-Set: {SOURCE}")
    target.mkdir(parents=True,exist_ok=True)
    with zipfile.ZipFile(SOURCE) as archive:
        if set(archive.namelist()) != set(ASSETS):
            raise SystemExit("Logoarchiv stimmt nicht mit dem freigegebenen Set überein")
        for original,exported in ASSETS.items():
            data=archive.read(original)
            if not data.startswith(b"\x89PNG\r\n\x1a\n"):
                raise SystemExit(f"Logo ist keine gültige PNG-Datei: {original}")
            if len(data)<1000 or len(data)>2_000_000:
                raise SystemExit(f"Logo hat eine unerwartete Dateigröße: {original}")
            width,height=struct.unpack(">II",data[16:24])
            if width<64 or height<64 or width>4000 or height>4000:
                raise SystemExit(f"Logo hat unerwartete Bildabmessungen: {original}")
            if exported == "ts-favicon.png":
                data=clean_favicon(data)
                width,height=struct.unpack(">II",data[16:24])
            (target/exported).write_bytes(data)
            print(f"{exported}: {width}x{height} · {len(data):,} Bytes · SHA256 {hashlib.sha256(data).hexdigest()[:12]}")
        # Override the old PNGs with the approved, clean, truly transparent
        # variants. This removes the unwanted white edge and green rectangle.
        clean_variants = {
            "ts-logo-dunkel.png": (
                "tennis-schiri-ts09c-header-transparent.png",
                "0c6d86dfcb387caa490b2e6d6b9c5969219edfa1792022a0ff37ff9a3856c686",
            ),
            "ts-logo-hell.png": (
                "tennis-schiri-ts09c-light-transparent.png",
                "313a206c952970093166099ead0e1ed5f5d1a89f88082da8388fd68dd17ecf7c",
            ),
        }
        for output_name, (source_name, expected_sha256) in clean_variants.items():
            source = ROOT/"branding"/source_name
            if not source.is_file():
                raise SystemExit(f"Freigegebenes randloses Logo fehlt: {source}")
            clean = source.read_bytes()
            if hashlib.sha256(clean).hexdigest() != expected_sha256:
                raise SystemExit(f"Randloses Logo weicht vom freigegebenen Stand ab: {source}")
            if not clean.startswith(b"\x89PNG\r\n\x1a\n") or clean[25] != 6:
                raise SystemExit(f"Kein transparentes RGBA-PNG: {source}")
            (target/output_name).write_bytes(clean)
            print(f"Logo ohne Rahmen aktiviert: {output_name} ({len(clean):,} Bytes)")
        # Legacy /favicon.svg should display the same original TS-09c symbol,
        # never the older hand-drawn icon. Embedded PNG avoids outside resources.
        icon=(target/"ts-favicon.png").read_bytes()
        size=struct.unpack(">I",icon[16:20])[0]
        encoded=base64.b64encode(icon).decode("ascii")
        legacy=ROOT/"public"/"favicon.svg"
        legacy.write_text(
            f'<svg xmlns="http://www.w3.org/2000/svg" width="{size}" height="{size}" viewBox="0 0 {size} {size}" role="img" aria-label="Tennis-Schiri">'
            '<image href="data:image/png;base64,'+encoded+f'" width="{size}" height="{size}"/>'
            '</svg>\\n',encoding="utf-8"
        )
        print("Legacy favicon.svg auf Original-TS-09c-Grafik aktualisiert")


def main():
    p=argparse.ArgumentParser()
    p.add_argument("--output",type=Path,required=True)
    a=p.parse_args()
    export(a.output)

if __name__=="__main__":
    main()
