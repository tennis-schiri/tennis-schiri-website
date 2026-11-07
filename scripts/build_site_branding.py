#!/usr/bin/env python3
"""Use the approved TS-09c design files from the archived Google Drive logo set.

The source ZIP is checked into branding/. No other fonts or external assets required.
All logo images are copied byte-for-byte, without recreating the approved typography.
"""
import argparse
import hashlib
import struct
import sys
import zipfile
from pathlib import Path

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
            (target/exported).write_bytes(data)
            print(f"{exported}: {width}x{height} · {len(data):,} Bytes · SHA256 {hashlib.sha256(data).hexdigest()[:12]}")

def main():
    p=argparse.ArgumentParser()
    p.add_argument("--output",type=Path,required=True)
    a=p.parse_args()
    export(a.output)

if __name__=="__main__":
    main()
