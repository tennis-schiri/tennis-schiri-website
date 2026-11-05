"""Local tests without Instagram tokens and without external network requests."""
import importlib.util
from pathlib import Path
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location("sync_instagram", ROOT / "scripts/sync_instagram.py")
ig = importlib.util.module_from_spec(spec)
spec.loader.exec_module(ig)


class TestInstagramSync(unittest.TestCase):
    def test_instagram_permalink_validation(self):
        self.assertTrue(ig.validate_media_link("https://www.instagram.com/p/ABC123/"))
        self.assertTrue(ig.validate_media_link("https://www.instagram.com/reel/XYZ123/"))
        self.assertFalse(ig.validate_media_link("https://instagram.com.attacker.com/post"))
        self.assertFalse(ig.validate_media_link("javascript:alert(1)"))
        self.assertFalse(ig.validate_media_link("http://www.instagram.com/p/ABC123/"))

    def test_supported_image_signatures(self):
        self.assertEqual(ig.image_extension(b"\xff\xd8\xfftest"), ".jpg")
        self.assertEqual(ig.image_extension(b"\x89PNG\r\n\x1a\n"), ".png")
        self.assertEqual(ig.image_extension(b"RIFF" + (10).to_bytes(4, "little") + b"WEBPtest"), ".webp")
        self.assertIsNone(ig.image_extension(b"fake"))

    def test_unchanged_cached_post_keeps_local_image(self):
        with tempfile.TemporaryDirectory() as temp:
            image = Path(temp) / "public/assets/instagram/tennilo-1-123.jpg"
            image.parent.mkdir(parents=True)
            image.write_bytes(b"\xff\xd8\xfftest")
            old = {"accounts": [{"key": "tennilo", "posts": [{"id": "123", "image": "assets/instagram/tennilo-1-123.jpg"}]}]}
            with patch.object(ig, "ROOT", Path(temp)):
                self.assertEqual(ig.previous_post_image(old, "tennilo", 0, "123"), "assets/instagram/tennilo-1-123.jpg")
                self.assertIsNone(ig.previous_post_image(old, "tennilo", 0, "124"))

    def test_unified_login_assigned_account_lookup(self):
        data = {"data": [{"id": "17841400000000123", "username": "tennis.schiri"}]}
        with patch.object(ig, "json_get", return_value=data) as api:
            self.assertEqual(ig.find_account_id("token", "tennis.schiri"), "17841400000000123")
            self.assertEqual(api.call_args.args[0], "/me/assigned_instagram_accounts")

    def test_unassigned_account_fails_closed(self):
        with patch.object(ig, "json_get", return_value={"data": []}):
            with self.assertRaises(ig.SyncError):
                ig.find_account_id("token", "tennilo.app")

    def test_media_limit_and_download_mock(self):
        result = {"data": [
            {"id": "123", "permalink": "https://www.instagram.com/p/one/", "media_type": "IMAGE", "media_url": "https://cdninstagram.com/1.jpg", "caption": "Erster Beitrag", "timestamp": "2026-11-04T12:00:00+0000"},
            {"id": "124", "permalink": "https://www.instagram.com/p/two/", "media_type": "VIDEO", "thumbnail_url": "https://cdninstagram.com/2.jpg", "caption": "Zweiter Beitrag", "timestamp": "2026-11-03T12:00:00+0000"},
        ]}
        with patch.object(ig, "json_get", return_value=result), patch.object(ig, "download_image", return_value=(b"\xff\xd8\xfftest", ".jpg")), patch.object(ig, "write_image", side_effect=lambda key, slot, post_id, data, ext: f"assets/instagram/{key}-{slot+1}-{post_id}{ext}"):
            posts = ig.get_posts("tennilo", "17841400000000123", "testtoken", {})
            self.assertEqual(len(posts), 2)
            self.assertEqual(posts[0]["id"], "123")
            self.assertEqual(posts[1]["image"], "assets/instagram/tennilo-2-124.jpg")


if __name__ == "__main__":
    unittest.main()