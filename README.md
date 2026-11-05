# Neue Website tennis-schiri.de

Entwicklung der neuen Website für **tennis-schiri.de**. Die bisherige WordPress-Seite bleibt bis zum finalen Wechsel unverändert.

## Testumgebung und Veröffentlichung

- Testadresse: https://neu.tennis-schiri.de/
- Branch: `main`
- Website-Dateien im Verzeichnis `public/`
- Veröffentlichungsworkflow: `.github/workflows/deploy.yml`
- GitHub → Actions → **Website per FTPS veröffentlichen** → **Run workflow**
- GitHub Secrets: `FTP_HOST`, `FTP_USER`, `FTP_PASSWORD`
- FTP-Benutzer bei ALL-INKL unbedingt auf das Verzeichnis der Test-Subdomain beschränken (`/tennis-schiri-neu/`)
- Der Workflow lädt nur namentlich freigegebene Dateien hoch; **keine Löschbefehle**

## Seiten

- Startseite: `public/index.html`
- Turniere: `public/turniere/index.html`
- Über mich: `public/ueber-mich/index.html`
- Referenzen: `public/referenzen/index.html`
- Leistungen: `public/leistungen/index.html`
- Regelwissen: `public/regelwissen/index.html`
- Kontakt: `public/kontakt/index.html`
- Aktuelles: `public/aktuelles/index.html`
- Design: `public/assets/site.css`
- Persönliche Fotos: `public/assets/michael-wiedeck-*.jpg`

## Übernommene Inhalte

Öffentliche Inhalte der vorherigen Website (Stand 5. November 2026): Profil, Qualifikationen und Vereinsarbeit; Referenzen aus DTB-, LK-, JWTS- und Beach-Tennis-Turnieren; Turniertermine für 2026/2027; Leistungen, Workshops, Livescore-Meldung und Turnier-Community. Bilder aus dem von Michael Wiedeck bereitgestellten Google-Drive-Ordner wurden als eigenständige JPG-Dateien eingebunden.

## Vor dem endgültigen Domainwechsel

- Termine, Anmeldelinks und genaue Ausschreibungen erneut prüfen, direkte Anmeldelinks ergänzen.
- Impressum und Datenschutzerklärung **inhaltlich und rechtlich prüfen und neu als lokale Seiten anlegen**. Die Links zeigen derzeit noch auf die alte WordPress-Website. Nicht als endgültige Lösung verwenden.
- Inhalte der Regelwissensrubrik mit offiziellen Regelreferenzen erweitern.
- Alte WordPress-URLs auf neue Unterseiten per 301 umleiten, soweit nötig.
- `noindex` und `robots.txt` erst nach dem Produktivwechsel und rechtlicher Prüfung anpassen.
- Optionale Funktionen (bisheriger Buchungskalender, Partner, Anfrageformulare) vor dem Abschalten von WordPress separat bewerten.
