# tennis-schiri.de – neue Website

Dieses Repository enthält die neue Website für **tennis-schiri.de**.

- Entwicklungsadresse: https://neu.tennis-schiri.de
- Quelldateien: `public/`
- Veröffentlichung: GitHub Actions → **Website per FTPS veröffentlichen** (manuell)
- FTP-Zugangsdaten: GitHub Actions Repository Secrets `FTP_HOST`, `FTP_USER`, `FTP_PASSWORD`

## Sicherheit

Der FTP-Benutzer muss bei ALL-INKL ausschließlich auf das Verzeichnis der neuen Subdomain (`/tennis-schiri-neu/`) begrenzt sein. Der Workflow nutzt absichtlich weder Lösch- noch Synchronisierungsbefehle. Die bestehende WordPress-Seite darf nicht verändert werden.

Die Entwicklungsseite hat `noindex` und eine `robots.txt`. Das ist **kein Passwortschutz**. Die rechtlichen Seiten, Termine und externen Links des Prototyps sind vor dem produktiven Umzug zu prüfen.

## Veröffentlichung

GitHub → Actions → **Website per FTPS veröffentlichen** → Run workflow → main.
