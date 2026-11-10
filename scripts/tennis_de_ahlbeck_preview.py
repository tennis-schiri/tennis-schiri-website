#!/usr/bin/env python3
"""Inspect one tennis.de tournament. Optional login only via GitHub Actions secrets.

Never log credentials, cookies, personal data or raw HTML. Apart from the
login form, no data-changing actions are performed on tennis.de.
"""
import os
import re
import sys
import time
from urllib.parse import urlparse

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait

ALLOWED_HOSTS = {"www.tennis.de", "tennis.de", "account.tennis.de", "spieler.tennis.de"}

URL = "https://www.tennis.de/spielen/spielbetrieb/turniersuche.html#detail/870524"
SAFE_TERMS = re.compile(
    r"turnier|krohn|ahlbeck|2027|august|veranstalt|austrag|kategor|ranglist|"
    r"meldung|meldeschluss|nennschluss|auslosung|wettbewerb|konkurrenz|"
    r"altersklass|senior|damen|herren|doppel|mixed|spielbeginn|nenngeld|"
    r"startgeld|belag|platzanlage|ort\b|b[aä]lle|kontakt", re.I)
BLOCKED_TERMS = re.compile(
    r"meldeliste|teilnehmerliste|zulassungsliste|tableau|spielerliste|"
    r"spielerprofil|registrierung|angemeldete spieler", re.I)


def extract(text):
    lines = [re.sub(r"\s+", " ", x).strip() for x in text.splitlines()]
    lines = [x for x in lines if x and len(x) < 240]
    result = []
    seen = set()
    for i, line in enumerate(lines):
        if BLOCKED_TERMS.search(line):
            continue
        if SAFE_TERMS.search(line):
            for candidate in lines[max(0, i - 1):min(len(lines), i + 3)]:
                if (candidate in seen or BLOCKED_TERMS.search(candidate)
                        or "@" in candidate or len(candidate) > 200):
                    continue
                # Skip long digit sequences and likely telephone numbers.
                if re.search(r"(?:\+?\d[\d -]{8,}\d)", candidate):
                    continue
                seen.add(candidate)
                result.append(candidate)
                if len(result) >= 100:
                    break
        if len(result) >= 100:
            break
    return result



def check_host(driver):
    host = urlparse(driver.current_url).hostname
    if host not in ALLOWED_HOSTS:
        raise ValueError("Anmeldeseite liegt nicht auf einer freigegebenen tennis.de-Domain")


def visible(elements):
    return [el for el in elements if el.is_displayed() and el.is_enabled()]


def login_form(driver):
    """Find the visible login fields; ignore registration and newsletter forms."""
    passwords = visible(driver.find_elements(By.CSS_SELECTOR, "input[type='password']"))
    for password in passwords:
        forms = password.find_elements(By.XPATH, "./ancestor::form[1]")
        form = forms[0] if forms else driver
        email_fields = visible(form.find_elements(
            By.CSS_SELECTOR, "input[type='email'],input[name*='email'],input[name*='login'],input[autocomplete='username']"
        ))
        if not email_fields:
            email_fields = [el for el in visible(form.find_elements(By.CSS_SELECTOR, "input[type='text']"))
                            if "mail" in ((el.get_attribute("placeholder") or "") +
                                          (el.get_attribute("name") or "") +
                                          (el.get_attribute("id") or "")).lower()]
        if email_fields:
            return form, email_fields[0], password
    return None


def prepare_login_page(driver):
    # First follow tennis.de's own login entry point to preserve SSO state.
    buttons = visible(driver.find_elements(
        By.XPATH, "//a[normalize-space()='Login'] | //button[normalize-space()='Login']"))
    if buttons:
        buttons[-1].click()
        time.sleep(3)
        if len(driver.window_handles) > 1:
            driver.switch_to.window(driver.window_handles[-1])
        check_host(driver)
    if not login_form(driver):
        driver.get("https://account.tennis.de/external-application-login-dialog")
    check_host(driver)
    return WebDriverWait(driver, 15).until(lambda d: login_form(d))


def try_login(driver, username, password):
    if not username or not password:
        print("::warning::NULIGA_USERNAME und/oder NULIGA_PASSWORD nicht hinterlegt.")
        return False
    try:
        form, email_field, pass_field = prepare_login_page(driver)
        check_host(driver)
        email_field.clear()
        email_field.send_keys(username)
        pass_field.clear()
        pass_field.send_keys(password)
        # Submit only the login form. Never interact with the registration form.
        buttons = visible(form.find_elements(By.CSS_SELECTOR, "button[type='submit'],input[type='submit']"))
        if buttons:
            buttons[0].click()
        else:
            pass_field.submit()
        time.sleep(5)
        check_host(driver)
        return True
    except Exception as error:
        print(f"::warning::Anmeldeversuch konnte nicht abgeschlossen werden: {type(error).__name__}")
        return False


def main():
    parsed = urlparse(URL)
    if (parsed.scheme, parsed.netloc, parsed.path, parsed.fragment) != (
        "https", "www.tennis.de", "/spielen/spielbetrieb/turniersuche.html",
        "detail/870524"
    ):
        raise ValueError("Unzulässige URL")
    options = Options()
    options.add_argument("--headless=new")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--disable-extensions")
    options.add_argument("--window-size=1440,1080")
    options.add_argument("--lang=de-DE")
    driver = webdriver.Chrome(options=options)
    try:
        driver.set_page_load_timeout(45)
        driver.get(URL)
        mode = os.environ.get("TENNIS_DE_LOGIN", "0") == "1"
        attempted_login = False
        if mode:
            attempted_login = try_login(driver, os.environ.get("NULIGA_USERNAME", ""),
                                        os.environ.get("NULIGA_PASSWORD", ""))
            # Return to the selected tournament after SSO.
            if attempted_login:
                driver.get(URL)
        deadline = time.monotonic() + 30
        best = ""
        while time.monotonic() < deadline:
            text = driver.execute_script("return document.body ? document.body.innerText : ''")
            if len(text) > len(best):
                best = text
            # A real tournament detail must contain its name; the general
            # search shell alone is not enough for a successful inspection.
            if re.search(r"manfred.{0,15}krohn", text, re.I) and len(text) > 600:
                best = text
                break
            time.sleep(2)
        print("## tennis.de: Ahlbeck-Vorschau 870524")
        print("Anmeldeversuch:", "ja" if attempted_login else "nein")
        print("Webadresse:", URL)
        print("Geladene sichtbare Zeichen:", len(best))
        print("Turnierdetail erkannt:", bool(re.search(r"manfred.{0,15}krohn", best, re.I)))
        items = extract(best)
        for item in items:
            print("•", item)
        if not re.search(r"manfred.{0,15}krohn", best, re.I):
            print("::warning::Nur das Suchformular, nicht die eigentlichen Turnierdetails sichtbar. "
                  "Die Seite erfordert möglicherweise einen anderen Zugriff.")
        with open(__import__("os").environ.get("GITHUB_STEP_SUMMARY", "/dev/null"),
                  "a", encoding="utf-8") as summary:
            summary.write("## tennis.de – öffentliche Ahlbeck-Vorschau\n\n")
            summary.write("Turnier-ID 870524 · keine Turnieranmeldung und keine Datenübernahme\n\n")
            summary.write("Turnierdetail erkennbar: **" +
                          ("ja" if re.search(r"manfred.{0,15}krohn", best, re.I) else "nein") +
                          "**\n\n")
            for item in items:
                summary.write("- " + item.replace("<", "&lt;").replace(">", "&gt;") + "\n")
        return 0
    finally:
        driver.quit()


if __name__ == "__main__":
    sys.exit(main())
