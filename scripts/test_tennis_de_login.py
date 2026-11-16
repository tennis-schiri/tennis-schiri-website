"""Offline browser tests: never select registration instead of tennis.de login."""
import unittest
from unittest.mock import patch
from types import SimpleNamespace
from urllib.parse import quote

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from tennis_de_ahlbeck_preview import login_form, account_login_form


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


    def test_account_login_separate_wrappers(self):
        self.page("""
        <div id="username-wrap"><input type="text" aria-label="Benutzername"></div>
        <div id="password-wrap"><input type="password"></div>
        <div id="button-wrap"><button type="submit">Login</button></div>
        """)
        self.assertIsNone(login_form(self.driver))
        with patch("tennis_de_ahlbeck_preview.urlparse",
                   return_value=SimpleNamespace(hostname="account.tennis.de")):
            match = account_login_form(self.driver)
        self.assertIsNotNone(match)
        submit, username, password = match
        self.assertEqual(submit.text, "Login")
        self.assertEqual(username.get_attribute("type"), "text")
        self.assertEqual(password.get_attribute("type"), "password")

    def test_account_login_rejects_ambiguous_forms(self):
        self.page("""
        <input type="email"><input type="password">
        <input type="password"><button>Login</button>
        """)
        with patch("tennis_de_ahlbeck_preview.urlparse",
                   return_value=SimpleNamespace(hostname="account.tennis.de")):
            self.assertIsNone(account_login_form(self.driver))

    def test_account_login_denies_other_domains(self):
        self.page("""
        <input type="email"><input type="password"><button>Login</button>
        """)
        self.assertIsNone(account_login_form(self.driver))



if __name__ == "__main__":
    unittest.main()
