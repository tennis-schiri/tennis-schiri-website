#!/usr/bin/env python3
"""Refresh both first-party Instagram feeds for the static tennis-schiri website.

Authentication: Meta Instagram API with Unified Login (Business Integration
System User Access Tokens). Tokens are supplied only as GitHub Action secrets;
they are never saved to the repository or sent to the public website.
"""
from __future__ import annotations

import datetime as dt
import ipaddress
import json
import os
from pathlib import Path
import re
import sys
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
DATA = ROOT / "public/data/instagram.json"
IMAGES = ROOT / "public/assets/instagram"
API = "https://graph.facebook.com/v26.0"
MAX_POSTS = 3
MAX_IMAGE_BYTES = 8 * 1024 * 1024
ACCOUNTS = (
    ("tennis-schiri", "tennis.schiri", "Tennis-Schiri", "IG_TOKEN_TENNIS_SCHIRI"),
    ("tennilo", "tennilo.app", "TENNILO", "IG_TOKEN_TENNILO"),
)


class SyncError(Exception):
    pass


def now_utc() -> dt.datetime:
    return dt.datetime.now(dt.timezone.utc)


def json_get(path: str, token: str, params: dict[str, str | int]) -> dict:
    # Token in Authorization header, not in loggable query strings.
    query = urllib.parse.urlencode(params)
    url = f"{API}{path}?{query}"
    request = urllib.request.Request(url, headers={
        "Authorization": f"Bearer {token}",
        "Accept": "application/json",
        "User-Agent": "TennisSchiri-InstagramFeed/1.0",
    })
    try:
        with urllib.request.urlopen(request, timeout=25) as response:
            payload = json.load(response)
    except urllib.error.HTTPError as exc:
        raise SyncError(f"Meta API HTTP {exc.code} auf {path} (Token und URL nicht protokolliert)") from None
    except (urllib.error.URLError, TimeoutError, ValueError) as exc:
        raise SyncError(f"Meta API nicht erreichbar oder ungültige Antwort auf {path}: {type(exc).__name__}") from None
    if not isinstance(payload, dict) or "error" in payload:
        raise SyncError(f"Meta API meldet einen Fehler auf {path}")
    return payload


def validate_media_link(value: object) -> bool:
    if not isinstance(value, str):
        return False
    parsed = urllib.parse.urlsplit(value)
    return parsed.scheme == "https" and parsed.hostname in ("instagram.com", "www.instagram.com")


def image_extension(data: bytes) -> str | None:
    if data.startswith(b"\xff\xd8\xff"):
        return ".jpg"
    if data.startswith(b"\x89PNG\r\n\x1a\n"):
        return ".png"
    if data.startswith(b"RIFF") and data[8:12] == b"WEBP":
        return ".webp"
    return None


def download_image(url: str) -> tuple[bytes, str]:
    parsed = urllib.parse.urlsplit(url)
    host = (parsed.hostname or "").lower()
    if parsed.scheme != "https" or not host or host in ("localhost", "localhost.localdomain"):
        raise SyncError("Unsichere Medienadresse verworfen")
    try:
        ip = ipaddress.ip_address(host)
        if not ip.is_global:
            raise SyncError("Nichtöffentliche Medienadresse verworfen")
    except ValueError:
        pass
    if any(x in parsed.netloc for x in ("@",)):
        raise SyncError("Medienadresse mit Anmeldedaten verworfen")
    request = urllib.request.Request(url, headers={"User-Agent": "TennisSchiri-InstagramFeed/1.0"})
    try:
        with urllib.request.urlopen(request, timeout=35) as response:
            data = response.read(MAX_IMAGE_BYTES + 1)
    except (urllib.error.URLError, TimeoutError, ValueError) as exc:
        raise SyncError(f"Bilddownload nicht möglich: {type(exc).__name__}") from None
    if len(data) > MAX_IMAGE_BYTES:
        raise SyncError("Bild größer als 8 MB")
    ext = image_extension(data)
    if not ext:
        raise SyncError("Medienadresse lieferte kein unterstütztes Bild")
    return data, ext


def first_image(media: dict, token: str) -> str | None:
    kind = media.get("media_type")
    if kind == "IMAGE":
        return media.get("media_url")
    if kind == "VIDEO":
        return media.get("thumbnail_url")
    if kind == "CAROUSEL_ALBUM":
        try:
            response = json_get(f"/{media['id']}/children", token,
                                {"fields": "media_type,media_url,thumbnail_url", "limit": 5})
        except (SyncError, KeyError):
            return media.get("media_url")
        for child in response.get("data", []):
            if not isinstance(child, dict):
                continue
            if child.get("media_type") == "IMAGE" and child.get("media_url"):
                return child["media_url"]
            if child.get("thumbnail_url"):
                return child["thumbnail_url"]
        return media.get("media_url")
    return None


def previous_post_image(old_json: dict, key: str, slot: int, post_id: str) -> str | None:
    for account in old_json.get("accounts", []):
        if account.get("key") == key:
            posts = account.get("posts", [])
            if len(posts) > slot and str(posts[slot].get("id")) == post_id:
                value = posts[slot].get("image", "")
                if re.fullmatch(r"assets/instagram/[a-z0-9-]+-[123]-[0-9]+\.(jpg|png|webp)", value):
                    path = ROOT / "public" / value
                    if path.is_file() and path.stat().st_size:
                        return value
    return None


def write_image(key: str, slot: int, post_id: str, data: bytes, ext: str) -> str:
    IMAGES.mkdir(parents=True, exist_ok=True)
    name = f"{key}-{slot + 1}-{post_id}{ext}"
    filename = IMAGES / name
    if not filename.exists() or filename.read_bytes() != data:
        filename.write_bytes(data)
    return "assets/instagram/" + name


def get_posts(key: str, account_id: str, token: str, old_json: dict) -> list[dict]:
    result = json_get(f"/{account_id}/media", token, {
        "fields": "id,caption,media_type,media_url,thumbnail_url,permalink,timestamp", "limit": 18,
    })
    data = result.get("data", [])
    if not isinstance(data, list):
        raise SyncError(f"Ungültige Beitragsliste für {key}")
    media_list = sorted((item for item in data if isinstance(item, dict)),
                        key=lambda item: str(item.get("timestamp", "")), reverse=True)
    posts: list[dict] = []
    for media in media_list:
        if len(posts) >= MAX_POSTS:
            break
        permalink = media.get("permalink")
        if not validate_media_link(permalink):
            continue
        post_id = str(media.get("id", ""))
        if not post_id.isdecimal():
            continue
        slot = len(posts)
        previous = previous_post_image(old_json, key, slot, post_id)
        if previous:
            image = previous
        else:
            source = first_image(media, token)
            if not isinstance(source, str):
                continue
            try:
                bytes_, ext = download_image(source)
                image = write_image(key, slot, post_id, bytes_, ext)
            except SyncError as exc:
                print(f"Hinweis: Vorschau von {key}, Beitrag {post_id} übersprungen ({exc})", file=sys.stderr)
                continue
        caption = re.sub(r"\s+", " ", str(media.get("caption") or "")).strip()[:500]
        posts.append({
            "id": post_id, "caption": caption, "image": image,
            "permalink": permalink, "timestamp": str(media.get("timestamp") or ""),
        })
    return posts



def find_account_id(token: str, username: str) -> str:
    """Find an IG professional account explicitly assigned to the system user."""
    result = json_get("/me/assigned_instagram_accounts", token, {
        "fields": "id,username", "limit": 100,
    })
    accounts = result.get("data", [])
    if not isinstance(accounts, list):
        raise SyncError("Meta lieferte eine ungültige Liste freigegebener Instagram-Konten")
    for account in accounts:
        if not isinstance(account, dict):
            continue
        if str(account.get("username", "")).casefold() == username.casefold():
            account_id = str(account.get("id") or "")
            if account_id.isdecimal():
                return account_id
    raise SyncError(
        f"@{username} ist dem Unified-Login-Systemnutzer nicht zugewiesen. "
        "Bitte Freigabe des Instagram-Kontos in der Meta-App prüfen "
        "(und ggf. Unified Login neu autorisieren)."
    )


def run() -> None:
    # Read tokens fresh on every run; no obsolete 60-day refresh or token files.
    tokens = {}
    for key, username, label, env in ACCOUNTS:
        token = os.getenv(env, "").strip()
        if not token:
            raise SyncError(f"GitHub Secret {env} fehlt")
        tokens[key] = token

    old_json = json.loads(DATA.read_text(encoding="utf-8")) if DATA.exists() else {}
    accounts = []
    now = now_utc()

    # Verify BOTH accounts and download BOTH feeds before committing public JSON.
    for key, username, label, _env in ACCOUNTS:
        token = tokens[key]
        account_id = find_account_id(token, username)
        posts = get_posts(key, account_id, token, old_json)
        accounts.append({
            "key": key, "username": username, "label": label,
            "profile_url": f"https://www.instagram.com/{username}/", "posts": posts,
        })
        print(f"@{username}: {len(posts)} Vorschauen synchronisiert")

    result = {"updated_at": old_json.get("updated_at"), "accounts": accounts}
    if accounts != old_json.get("accounts") or not result["updated_at"]:
        result["updated_at"] = now.replace(microsecond=0).isoformat().replace("+00:00", "Z")
        DATA.parent.mkdir(parents=True, exist_ok=True)
        DATA.write_text(json.dumps(result, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        print("Instagram-Daten aktualisiert")
    else:
        print("Keine neuen öffentlichen Beiträge")

    # Delete obsolete cache paths only AFTER a successful sync of both accounts.
    referenced = {str((ROOT / "public" / post["image"]).resolve())
                  for account in accounts for post in account["posts"]}
    if IMAGES.exists():
        for image in IMAGES.iterdir():
            if image.is_file() and image.suffix.lower() in (".jpg", ".png", ".webp"):
                if str(image.resolve()) not in referenced:
                    image.unlink()


if __name__ == "__main__":
    try:
        run()
    except SyncError as error:
        print(f"Fehler beim Instagram-Abgleich: {error}", file=sys.stderr)
        sys.exit(1)