"""Offline browser tests: never select registration instead of tennis.de login."""
import unittest
from urllib.parse import quote

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from tennis_de_ahlbeck_preview import login_form


class TennisDeLoginSelectorTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        options = Options()
        options.add_argument("--headless=new")
        options.add_argument("--no-sandbox")
        options.add_argument("--disable-dev-shm-usage")
        cls.driver = webdriver.Chrome(options=options)

    @classmethod
    def tearDownClass(cls):
        cls.driver.quit()

    def page(self, html):
        self.driver.get("data:text/html;charset=utf-8," + quote(html))

    def test_signin_selected_over_registration(self):
        self.page("""
        <form id="registration">
           <input type="email"><input type="password">
           <input type="password"><button type="submit">Registrieren</button>
        </form>
        <form id="login">
           <input type="email"><input type="password">
           <button type="submit">Einloggen</button>
        </form>""")
        match = login_form(self.driver)
        self.assertIsNotNone(match)
        submit, email, pwd = match
        self.assertEqual(submit.text, "Einloggen")
        self.assertEqual(email.find_element(By.XPATH, "ancestor::form[1]").get_attribute("id"), "login")
        self.assertEqual(pwd.find_element(By.XPATH, "ancestor::form[1]").get_attribute("id"), "login")

    def test_no_login_when_only_registration_is_present(self):
        self.page("""
        <form><input type="email"><input type="password">
          <input type="password"><button type="submit">Registrieren</button>
        </form>""")
        self.assertIsNone(login_form(self.driver))

    def test_no_ambiguous_login_form(self):
        self.page("""
        <div><input type="email"><input type="password">
          <input type="password"><button>Einloggen</button></div>""")
        self.assertIsNone(login_form(self.driver))


if __name__ == "__main__":
    unittest.main()
