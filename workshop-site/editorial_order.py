#!/usr/bin/env python3
"""Apply editorial order and original image bindings to the protected web presenter.

The 136 slide graphics are numbered in the original PowerPoint order. The web
narrative is intentionally different, but each slide retains assetNo and the
original asset names. Never silently drop or renumber a video.
"""
import json
import re
from pathlib import Path

BASE = Path("_workshop_upload")
DECK = BASE / "_private/index.html"
APP = BASE / "assets/app.js"
CSS = BASE / "assets/app.css"
CATALOG = BASE / "_private/slides.json"


def replace_once(text: str, old: str, new: str, description: str) -> str:
    if text.count(old) != 1:
        raise SystemExit(f"{description}: expected marker exactly once (got {text.count(old)})")
    return text.replace(old, new, 1)


def main() -> None:
    html = DECK.read_text(encoding="utf-8")
    match = re.search(
        r'(<script id="workshop-data" type="application/json">)(.*?)(</script>)',
        html, flags=re.S,
    )
    if match is None:
        raise SystemExit("Embedded workshop slides not found")
    payload = json.loads(match.group(2))
    slides = payload.get("slides", [])
    if len(slides) != 136:
        raise SystemExit(f"Expected 136 slides, got {len(slides)}")
    source_numbers = [x.get("assetNo") for x in slides]
    if sorted(source_numbers) != list(range(1, 137)):
        raise SystemExit("Editorial sequence must contain every original image exactly once")
    if [x.get("no") for x in slides] != list(range(1, 137)):
        raise SystemExit("Displayed slide numbers must be consecutive")
    if len([x for x in slides if x.get("type") == "video"]) != 25:
        raise SystemExit("Expected 25 original videos")
    if len([x for x in slides if x.get("type") == "question"]) != 27:
        raise SystemExit("Expected 27 questions")
    for i, slide in enumerate(slides):
        if slide.get("type") == "question":
            if i + 1 >= len(slides):
                raise SystemExit(f"Missing answer after question at {i + 1}")
            answer = slides[i + 1]
            if answer.get("type") != "answer" or answer.get("title") != slide.get("title"):
                raise SystemExit(f"Question/answer pair broken at {i + 1}")

    js = APP.read_text(encoding="utf-8")
    js = replace_once(
        js, "img.src=renderImage(s.no)", "img.src=renderImage(s.assetNo||s.no)",
        "slide image must use original asset number",
    )
    js = replace_once(
        js, "video.poster=renderImage(s.no)",
        "video.poster=renderImage(s.assetNo||s.no)",
        "video poster must use original asset number",
    )
    js = replace_once(
        js, "p.src=renderImage(next.no)",
        "p.src=renderImage(next.assetNo||next.no)",
        "next image preload must use original asset number",
    )
    marker = "counter.textContent=String(s.no).padStart(3,'0')+' / '+S.length;"
    js = replace_once(
        js, marker,
        marker + "byId('editorial-counter').textContent=String(s.no).padStart(3,'0');",
        "new slide counter overlay",
    )
    APP.write_text(js, encoding="utf-8")

    html = replace_once(
        html, '<button class="clickarea previous"',
        '<span id="editorial-counter" class="editorial-counter" aria-hidden="true">001</span>'
        '<button class="clickarea previous"',
        "insert editorial counter on slide",
    )
    html = html.replace("?v=20261117-1", "?v=20261117-editorial2")
    DECK.write_text(html, encoding="utf-8")

    css = CSS.read_text(encoding="utf-8")
    css += (
        "\n/* Editorial slide number masks the number baked into legacy images. */\n"
        ".editorial-counter{position:absolute;right:1.15%;bottom:1.3%;"
        "z-index:6;min-width:4.4%;text-align:center;"
        "background:#0a1b12;color:#d2fc46;padding:2px 5px;"
        "border-radius:3px;font:850 clamp(9px,1.05vw,15px) Inter,system-ui,sans-serif;"
        "letter-spacing:.05em;pointer-events:none}\n"
    )
    CSS.write_text(css, encoding="utf-8")

    CATALOG.write_text(json.dumps(payload, ensure_ascii=False, separators=(",", ":")),
                       encoding="utf-8")

    order = {s["assetNo"]: s["no"] for s in slides}
    for original, requirement in {107: 8, 108: 56, 113: 67, 117: 73, 135: 136}.items():
        if order[original] != requirement:
            raise SystemExit(f"Unexpected position for original slide {original}")

    print("Editorial order verified: all 136 slides, 27 Q/A pairs, 25 videos.")
    print("Original asset mapping and slide counter corrected for web presentation.")


if __name__ == "__main__":
    main()
