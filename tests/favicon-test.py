#!/usr/bin/env python3
"""Regression checks for the TS09c favicon without its old white frame."""
from pathlib import Path
from zipfile import ZipFile
import sys

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / 'scripts'))
from fix_favicon import clean_favicon, png_rgba

with ZipFile(ROOT/'branding'/'tennis-schiri-ts09c-logo-set.zip') as archive:
    original = archive.read('06_favicon.png')
clean = clean_favicon(original)
w,h,rows = png_rgba(clean)
if w != h or w < 100 or w > 300:
    raise SystemExit(f'Favicon must be square and correctly sized, got {w} x {h}')
for x,y in [(0,0),(w-1,0),(0,h-1),(w-1,h-1),(w//2,0),(0,h//2)]:
    if rows[y][4*x+3] != 0:
        raise SystemExit(f'Opaque frame at ({x}, {y})')
inside_white = 0
inside_green = 0
for row in rows:
    for j in range(0,len(row),4):
        r,g,b,a = row[j:j+4]
        if a > 200 and min(r,g,b) >= 220:
            inside_white += 1
        if a > 200 and g > r+12 and g > b+12:
            inside_green += 1
if inside_white < 100 or inside_green < 100:
    raise SystemExit(f'Original TS lettering or green sweep lost: white={inside_white}, green={inside_green}')
expected='/assets/branding/ts-favicon.png?v=20261109-1'
files=list((ROOT/'public').rglob('index.html'))+[ROOT/'public'/'turniere'/'termin.html']
for page in files:
    content=page.read_text(encoding='utf-8')
    if content.count(expected) != 2:
        raise SystemExit(f'Favicon or Apple icon missing cache refresh: {page}')
print(f'Favicon OK: transparent {w}x{h}; original TS artwork preserved; {len(files)} pages updated.')
