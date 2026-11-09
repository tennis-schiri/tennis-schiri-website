"""Synthetic tests for the read-only TVBB nuLiga HTML parser."""
import datetime as dt
import unittest

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


if __name__ == "__main__":
    unittest.main()
