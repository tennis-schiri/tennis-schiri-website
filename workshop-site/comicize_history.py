#!/usr/bin/env python3
"""Historische Originalillustrationen -> klar unterscheidbare Vintage-Comicbilder.
Quellen bleiben unverändert; die erzeugten Comics sind interpretierte Darstellungen.
Pillow-only, deterministisch, kein externer Onlinedienst.
"""
from pathlib import Path
from PIL import Image, ImageOps, ImageEnhance, ImageFilter, ImageChops
import sys

INPUT = Path("workshop-site/history-originals")
OUTPUT = Path("_workshop_upload/_private/comics")
NAMES = (
    "jeu-de-paume-original",
    "wingfield-original",
    "wimbledon-1877-original",
    "lawn-tennis-1887-original",
)

def comicize(filename: str) -> Path:
    infile = INPUT / (filename + ".jpg")
    if not infile.is_file():
        raise SystemExit(f"Historische Bildquelle fehlt: {infile}")
    src = ImageOps.exif_transpose(Image.open(infile)).convert("RGB")
    src.thumbnail((1800, 1200), Image.Resampling.LANCZOS)
    source_colors = ImageEnhance.Color(src).enhance(.86)
    grayscale = ImageOps.grayscale(src)
    # Farbdruck im 19.-Jahrhundert-Stil: Tuschekonturen mit warmem Papierton.
    colors = grayscale.resize((90, max(1, round(grayscale.height * 90 / grayscale.width))))
    low = ImageOps.autocontrast(colors)
    # Ist die Vorlage beinahe monochrom, die Tiefen und Lichter gezielt einfärben.
    if ImageChops.difference(source_colors, Image.merge("RGB", (grayscale,)*3)).resize((16,16)).getbbox() is None:
        colored = ImageOps.colorize(
            ImageOps.autocontrast(grayscale),
            black="#291b18", mid="#b78358", white="#fff0d4",
            midpoint=128,
        )
    else:
        poster = ImageOps.posterize(
            ImageEnhance.Contrast(ImageEnhance.Color(src).enhance(1.16)).enhance(1.14), 5
        )
        warm = Image.new("RGB", poster.size, "#dcb27b")
        colored = Image.blend(poster, warm, .16)
    # Echte Konturen aus der Vorlage, kein neuer erfundener historischer Sachverhalt.
    smooth = grayscale.filter(ImageFilter.GaussianBlur(.3))
    inverted_edges = ImageOps.invert(smooth.filter(ImageFilter.FIND_EDGES))
    # Leichte Tuschestärke, ohne im Original vorhandene Feinlinien zu zerstören.
    blended = ImageChops.multiply(colored, Image.blend(
        Image.new("RGB", colored.size, "white"),
        Image.merge("RGB", (inverted_edges,)*3),
        .23))
    dst = OUTPUT / (filename + "-comic.webp")
    dst.parent.mkdir(parents=True, exist_ok=True)
    blended.filter(ImageFilter.UnsharpMask(radius=.8,percent=92,threshold=5)).save(
        dst, format="WEBP", quality=90, method=6)
    with Image.open(dst) as check:
        if check.width < 700 or check.height < 600 or check.format != "WEBP":
            raise SystemExit(f"Comicquelle nicht ausreichend: {dst}")
    return dst

if __name__ == "__main__":
    generated = [comicize(name) for name in NAMES]
    assert len(generated) == len(NAMES)
    for path in generated:
        print(f"Historische Comic-Illustration: {path.name} ({path.stat().st_size} Bytes)")
