"""Synthetic tests for the read-only TVBB nuLiga HTML parser."""
import datetime as dt
import io
import json
import os
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

import nuliga_import
from nuliga_import import best_match, tournaments_from_html


SAMPLE = """
<table>
  <tr><th>Datum</th><th>Turnier</th><th>Konkurrenz</th><th>LK</th><th>Offen für</th></tr>
  <tr>
    <td rowspan="3">27.12. bis 27.12.</td>
    <td rowspan="3"><a href="https://www.tennis.de/">2. SUNBALL TENNIS Winter-LK-Cup in Bad Saarow 2026/2027</a><br>TC Bad Saarow</td>
    <td>Herren Einzel</td><td>1,0–25,0</td><td>Deutschland</td>
  </tr>
  <tr><td>Damen Einzel</td><td>1,0–25,0</td><td>Deutschland</td></tr>
  <tr><td>Mixed Aktive Doppel</td><td>1,0–25,0</td><td>Deutschland</td></tr>
  <tr>
    <td>27.12. bis 27.12.</td><td>Other Event Other Club</td>
    <td>Herren Einzel</td><td>2,0–25,0</td><td>Deutschland</td>
  </tr>
</table>
"""


class ReadOnlyParserTest(unittest.TestCase):
    def test_rowspan_competitions_and_exclusion(self):
        items = tournaments_from_html(SAMPLE, dt.date(2026, 12, 27))
        self.assertEqual(len(items), 2)
        self.assertEqual(len(items[0]["competitions"]), 3)
        self.assertEqual(items[0]["competitions"][1]["name"], "Damen Einzel")
        self.assertEqual(items[0]["competitions"][0]["lk"], "1,0–25,0")
        config = {
            "date_start": "2026-12-27",
            "title_contains": ["sunball tennis winter-lk-cup"],
            "club_contains": "tc bad saarow",
        }
        self.assertTrue(best_match(items[0], config))
        self.assertFalse(best_match(items[1], config))

    def test_only_exact_start_date_matches(self):
        items = tournaments_from_html(SAMPLE, dt.date(2026, 12, 27))
        config = {"date_start": "2027-12-27", "title_contains": ["sunball"]}
        self.assertFalse(best_match(items[0], config))


    def test_preview_without_public_match_does_not_write_and_is_not_a_ci_error(self):
        with tempfile.TemporaryDirectory() as temp:
            config_file = Path(temp) / "events.json"
            output_file = Path(temp) / "public.json"
            config_file.write_text(json.dumps({"events": [
                {"id": "turnier-neuenhagen-open-2027",
                 "date_start": "2027-06-15",
                 "title_contains": ["neuenhagen open"]}
            ]}), encoding="utf-8")
            env = dict(os.environ)
            env.pop("GITHUB_STEP_SUMMARY", None)
            stdout = io.StringIO()
            with patch.object(nuliga_import, "CONFIG", config_file), \
                 patch.object(nuliga_import, "DEST", output_file), \
                 patch.object(nuliga_import, "download", return_value=("https://tvbb.liga.nu/", "<table></table>")), \
                 patch("sys.argv", ["nuliga_import.py", "--event-id", "turnier-neuenhagen-open-2027"]), \
                 patch.dict(os.environ, env, clear=True), \
                 patch("sys.stdout", stdout):
                exit_code = nuliga_import.main()
            self.assertEqual(exit_code, 0)
            self.assertFalse(output_file.exists())
            self.assertIn("kein bestätigter Datenabgleich", stdout.getvalue())

    def test_apply_without_explicit_approval_does_not_write(self):
        with patch("sys.argv", ["nuliga_import.py", "--event-id", "turnier-neuenhagen-open-2027", "--apply"]), \
             patch.dict(os.environ, {"NULIGA_IMPORT_APPROVED": ""}, clear=True):
            self.assertEqual(nuliga_import.main(), 2)


if __name__ == "__main__":
    unittest.main()
