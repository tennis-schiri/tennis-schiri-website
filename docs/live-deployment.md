# Umstellung von WordPress auf die neue tennis-schiri.de

Stand: 05.11.2026. **Diese Anleitung bereitet den Livegang vor, stellt die Domain aber nicht selbst um.**

## Verzeichnisse beim Hoster

- `/ts/`: bisherige WordPress-Website (unter tennis-schiri.de); **unverändert erhalten**.
- `/tennis-schiri-neu/`: bestehende Staging-Website unter neu.tennis-schiri.de; mit `noindex` und `Disallow: /`.
- `/tennis-schiri-live/`: neu angelegter, bislang leerer Zielordner für die produktive statische Website.

## Voraussetzung: eigenes eingeschränktes FTP-Konto

1. Im [ALL-INKL KAS](https://kas.all-inkl.com/) → **FTP** → **Neuen FTP-Nutzer anlegen**.
2. Beschreibung etwa `GitHub Tennis-Schiri LIVE`.
3. **Pfad exakt `/tennis-schiri-live/`**, nicht das Hauptverzeichnis, nicht `/ts/` und nicht `/tennis-schiri-neu/`.
4. Zufälliges starkes Passwort wählen; FTP-Host, FTP-Benutzername und Passwort nur in einem Passwortmanager und als GitHub Actions Repository Secrets speichern.
5. Unter [GitHub Secrets](https://github.com/tennis-schiri/tennis-schiri-website/settings/secrets/actions) anlegen:
   - `FTP_LIVE_HOST` – Serverhost des neuen FTP-Zugangs (ohne Protokoll oder `:21`).
   - `FTP_LIVE_USER` – FTP-Benutzername.
   - `FTP_LIVE_PASSWORD` – FTP-Passwort.
6. Die vorhandenen Test-FTP-Secrets `FTP_HOST`, `FTP_USER`, `FTP_PASSWORD` unverändert lassen.

## Produktivdateien vorbereiten

Den manuellen GitHub-Workflow `.github/workflows/deploy-live.yml` starten. Dieser:
- prüft, dass alle drei **separaten** FTP-Secrets vorhanden sind;
- kopiert `public/` temporär in `_live_upload/`, ohne die Staging-Dateien zu verändern;
- entfernt ausschließlich aus der temporären Live-Kopie die `<meta name="robots" content="noindex, nofollow, noarchive">`-Tags (alle 11 HTML-Seiten müssen erfolgreich bereinigt werden);
- ersetzt ausschließlich in der Live-Kopie `robots.txt` durch `User-agent: *; Allow: /` (tatsächlich mit Zeilenumbruch);
- lädt die Dateien über verschlüsseltes FTPS ins **FTP-Root des eingeschränkten Live-Nutzers**;
- verändert keine Domain-Einstellungen und löscht keinerlei WordPress-Dateien.

Den Live-Deploy **erst** starten, wenn der neue eingeschränkte FTP-Zugang samt Secrets eingerichtet ist. Der neue Ordner wird dabei befüllt, die alte Website läuft weiter.

Der bestehende Instagram-Sync synchronisiert bereits die Testseite; nach Einrichtung aller drei Live-FTP-Secrets werden die Instagram-Vorschaudaten auch in den Live-Ordner geladen.

## Vor Umschaltung der Hauptdomain

- WordPress-Backup von UpdraftPlus (Datenbank, Plugins, Themes, Uploads und Sonstiges) liegt nach Angabe des Betreibers auf seinem PC; Backup-Dateien auf Vollständigkeit prüfen und sicher verwahren.
- Rechtliche Checkliste `docs/rechtlicher-livegang-check-2026.md` überprüfen.
- Alte URLs prüfen und nötige Weiterleitungen vorbereiten; ggf. alte WordPress-Blogbeiträge berücksichtigen.
- SSL/TLS und ggf. www-Einstellungen kontrollieren; durch Änderung des **Domain-Zielverzeichnisses** bei ALL-INKL sollten bestehende Mail-/DNS-Einstellungen unverändert bleiben.
- Der FTP-Live-Workflow muss vollständig grün sein, bevor die Domain auf den neuen Ordner zeigt.

## Domainumschaltung (separater bewusster Schritt)

Bei ALL-INKL KAS unter **Domain → tennis-schiri.de → Bearbeiten** das Ziel von `/ts/` auf `/tennis-schiri-live/` setzen. **Nicht vorher ändern.** Wenn `www.tennis-schiri.de` separat konfiguriert ist, die Zuordnung ebenfalls prüfen.

Anschließend unmittelbar prüfen: Startseite, Unterseiten, Impressum/Datenschutz, Instagram, Favicon, verlinkte Unterseiten, 404s, HTTPS, HTTP-/www-Weiterleitungen und Suchmaschinenindexierung.

**Rollback:** Falls nötig, Domain-Ziel auf `/ts/` zurückstellen. WordPress und Datenbank werden nicht entfernt.
