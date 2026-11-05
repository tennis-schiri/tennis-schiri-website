#!/usr/bin/env python3
"""Refresh both first-party Instagram feeds for the static tennis-schiri website.

Authentication: Meta's Instagram API with Instagram Login (professional accounts).
Access tokens never reach public/; the current values are stored as Fernet ciphertext
outside public/ and re-encrypted only when a refresh changes them.
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

from cryptography.fernet import Fernet, InvalidToken

ROOT = Path(__file__).resolve().parents[1]
DATA = ROOT / "public/data/instagram.json"
IMAGES = ROOT / "public/assets/instagram"
TOKEN_STATE = ROOT / "private/instagram-tokens.enc"
API = "https://graph.instagram.com/v25.0"
MAX_POSTS = 3
MAX_IMAGE_BYTES = 8 * 1024 * 1024
REFRESH_DAYS = 25
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


def refresh_token(token: str) -> tuple[str, int]:
    # Meta requires the access_token query parameter for this endpoint.
    params = urllib.parse.urlencode({
        "grant_type": "ig_refresh_token", "access_token": token,
    })
    request = urllib.request.Request(
        "https://graph.instagram.com/refresh_access_token?" + params,
        headers={"Accept": "application/json", "User-Agent": "TennisSchiri-InstagramFeed/1.0"},
    )
    try:
        with urllib.request.urlopen(request, timeout=25) as response:
            payload = json.load(response)
    except urllib.error.HTTPError as exc:
        raise SyncError(f"Token-Refresh HTTP {exc.code} (Details aus Datenschutzgründen ausgeblendet)") from None
    except (urllib.error.URLError, TimeoutError, ValueError) as exc:
        raise SyncError(f"Token-Refresh fehlgeschlagen: {type(exc).__name__}") from None
    if not isinstance(payload, dict) or not isinstance(payload.get("access_token"), str):
        raise SyncError("Token-Refresh lieferte keinen neuen Token")
    return payload["access_token"], int(payload.get("expires_in", 0))


def load_token_state(fernet: Fernet) -> dict:
    reauthorize = os.getenv("IG_REAUTHORIZE", "false").lower() == "true"
    if TOKEN_STATE.exists() and not reauthorize:
        try:
            state = json.loads(fernet.decrypt(TOKEN_STATE.read_bytes()))
            if not isinstance(state, dict) or not all(key in state for key, *_ in ACCOUNTS):
                raise ValueError("Unvollständiger Kontenstatus")
            return state
        except (InvalidToken, ValueError, KeyError, TypeError) as exc:
            raise SyncError(f"Token-Datei nicht lesbar: {type(exc).__name__}") from None
    state = {}
    for key, _username, _label, env in ACCOUNTS:
        initial = os.getenv(env, "").strip()
        if not initial:
            raise SyncError(f"GitHub Secret {env} fehlt (Ersteinrichtung oder erneute Autorisierung)")
        state[key] = {"access_token": initial, "refreshed_at": None}
    return state


def save_token_state(fernet: Fernet, state: dict) -> None:
    TOKEN_STATE.parent.mkdir(parents=True, exist_ok=True)
    payload = json.dumps(state, ensure_ascii=False, sort_keys=True).encode("utf-8")
    TOKEN_STATE.write_bytes(fernet.encrypt(payload))


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


def get_posts(key: str, token: str, old_json: dict) -> list[dict]:
    result = json_get("/me/media", token, {
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


def maybe_refresh(state: dict, key: str, token: str, now: dt.datetime) -> bool:
    refreshed_at = state[key].get("refreshed_at")
    if refreshed_at:
        try:
            days = (now - dt.datetime.fromisoformat(refreshed_at)).total_seconds() / 86400
            if days < REFRESH_DAYS:
                return False
        except ValueError:
            pass
    try:
        new_token, expires_in = refresh_token(token)
    except SyncError as exc:
        # A dashboard token issued < 24h ago is not yet refreshable.
        # The next scheduled run retries, without losing currently valid media.
        print(f"Achtung: Token von {key} konnte noch nicht verlängert werden: {exc}", file=sys.stderr)
        return False
    state[key] = {"access_token": new_token, "refreshed_at": now.isoformat()}
    print(f"Token von {key} erneuert (Gültigkeit ungefähr {expires_in // 86400} Tage)")
    return True


def run() -> None:
    try:
        fernet = Fernet(os.environ["IG_ENCRYPTION_KEY"].strip().encode("ascii"))
    except (KeyError, ValueError) as exc:
        raise SyncError("IG_ENCRYPTION_KEY fehlt oder ist ungültig (Fernet-Schlüssel benötigt)") from exc
    state = load_token_state(fernet)
    old_json = json.loads(DATA.read_text(encoding="utf-8")) if DATA.exists() else {}
    accounts = []
    now = now_utc()
    modified_tokens = not TOKEN_STATE.exists() or os.getenv("IG_REAUTHORIZE", "false").lower() == "true"

    # Verify BOTH accounts and download BOTH feeds before committing the public JSON.
    for key, username, label, _env in ACCOUNTS:
        token = state[key].get("access_token", "")
        if not isinstance(token, str) or not token:
            raise SyncError("Ungültiger gespeicherter Kontentoken")
        me = json_get("/me", token, {"fields": "user_id,username"})
        if str(me.get("username", "")).casefold() != username.casefold():
            raise SyncError(f"Der GitHub-Token für {key} gehört nicht zum erwarteten @{username}-Konto")
        posts = get_posts(key, token, old_json)
        accounts.append({
            "key": key, "username": username, "label": label,
            "profile_url": f"https://www.instagram.com/{username}/", "posts": posts,
        })
        print(f"@{username}: {len(posts)} Vorschauen synchronisiert")
        if maybe_refresh(state, key, token, now):
            modified_tokens = True

    if modified_tokens:
        save_token_state(fernet, state)
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