#!/usr/bin/env python3
"""Check that the two synchronized tournament pagination controls are wired up."""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
page = (ROOT / "public/turniere/index.html").read_text(encoding="utf-8")
js = (ROOT / "public/assets/tournament-calendar.js").read_text(encoding="utf-8")
css = (ROOT / "public/assets/site.css").read_text(encoding="utf-8")

top = 'tourney-pagination-top'
bottom = 'tourney-pagination'
for marker in [top, bottom, 'tourney-page-controls-top', 'tourney-page-controls',
               'tourney-page-status-top', 'tourney-page-status']:
    assert page.count(f'id="{marker}"') == 1, f"Pagination anchor must be unique: {marker}"

assert page.index(f'id="{top}"') < page.index('id="tourney-grid"'), "Top pager below events"
assert page.index('id="tourney-grid"') < page.index(f'id="{bottom}"'), "Bottom pager above events"

for pager in (top, bottom):
    match = re.search(
        rf'<nav class="tourney-pages[^"]*" id="{pager}"[^>]*>(.*?)</nav>',
        page, re.DOTALL
    )
    assert match, f"Missing pager nav: {pager}"
    content = match.group(1)
    assert content.index('class="tourney-page-controls"') < content.index(
        'class="tourney-page-status"'), f"Range status must be next to/after the controls ({pager})"

assert 'for(const pager of pagers)' in js, "Both pagers must be rendered/handled"
assert "pager.status.textContent=label;" in js, "Status not kept in sync"
assert "pager.nav.hidden=totalPages<=1" in js, "Single-page pager must be hidden"
assert "pageSize=5" in js, "Five events per page expected"
assert "'Turniere '+(first+1)+'–'+Math.min(upcoming.length,first+pageSize)+' von '+upcoming.length" in js
assert 'tourney-pages--top' in css and 'tourney-pages--bottom' in css
print("Tournament paging: top and bottom synchronized, labels adjacent, page size 5.")
