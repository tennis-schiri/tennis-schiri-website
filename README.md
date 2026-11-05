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
- Regelwissen (Coming soon): `public/regelwissen/index.html`
- Kontakt: `public/kontakt/index.html`
- Aktuelles: `public/aktuelles/index.html`
- Impressum (Entwurf): `public/impressum/index.html`
- Datenschutz (Entwurf): `public/datenschutz/index.html`
- Partner und Kooperationen: `public/partner/index.html`
- Design: `public/assets/site.css`
- Persönliche Fotos: `public/assets/michael-wiedeck-*.jpg`

## Übernommene Inhalte

Öffentliche Inhalte der vorherigen Website (Stand 5. November 2026): Profil, Qualifikationen und Vereinsarbeit; Referenzen aus DTB-, LK-, JWTS- und Beach-Tennis-Turnieren; Turniertermine für 2026/2027; Leistungen, Workshops, Livescore-Meldung und Turnier-Community. Bilder aus dem von Michael Wiedeck bereitgestellten Google-Drive-Ordner wurden als eigenständige JPG-Dateien eingebunden.

## Vor dem endgültigen Domainwechsel

- Termine und Ausschreibungen erneut prüfen. Die drei Winter-LK-Termine verlinken momentan auf den TVBB-Wochenkalender und die alte Turnierübersicht; die dortigen **individuellen** Anmeldelinks noch übernehmen, sobald ihre Ziel-URLs eindeutig ausgelesen werden können.
- Impressum und Datenschutzhinweise liegen nun lokal; **vor Livegang unbedingt vollständige ladungsfähige Anschrift, Rechtsgrundlagen und Hoster-Angaben abgleichen**. Die aktuellen Seiten sind als Entwürfe gekennzeichnet.
- Regelwissen bleibt vorerst auf Coming soon. Später mit geprüften Inhalten und offiziellen Regelreferenzen ausbauen.
- Alte WordPress-URLs auf neue Unterseiten per 301 umleiten, soweit nötig.
- `noindex` und `robots.txt` erst nach dem Produktivwechsel und rechtlicher Prüfung anpassen.
- Optionale Funktionen (bisheriger Buchungskalender, Partner, Anfrageformulare) vor dem Abschalten von WordPress separat bewerten.

## Turnierdaten 2026/2027 (Prüfstand 05.11.2026)

Für die drei SUNBALL Winter-LK-Cups am 27.12.2026, 30.01.2027 und 06.02.2027 wurden die **Konkurrenzen** anhand der jeweiligen offiziellen TVBB-Wochenkalender geprüft:
- https://tvbb.liga.nu/cgi-bin/WebObjects/nuLigaTENDE.woa/wa/tournamentCalendar?date=2026-12-21&federation=TVBB
- https://tvbb.liga.nu/cgi-bin/WebObjects/nuLigaTENDE.woa/wa/tournamentCalendar?date=2027-01-25&federation=TVBB
- https://tvbb.liga.nu/cgi-bin/WebObjects/nuLigaTENDE.woa/wa/tournamentCalendar?date=2027-02-01&federation=TVBB

Konkurrenzen (alle drei Turniere): Herren Einzel, Damen Einzel, Herren 30 Einzel, Damen 30 Einzel, U18 männlich Einzel, U18 weiblich Einzel, U14 männlich Einzel, U14 weiblich Einzel, Mixed Aktive Doppel.

**Anmeldeschluss und Auslosung:** In der öffentlich abrufbaren Fassung der tennis.de-Detailseiten nicht zuverlässig auslesbar. Daher in der Website ausdrücklich **„Auf tennis.de prüfen“** mit Link zum jeweiligen Turnier; keine geschätzten Datums-/Zeitangaben übernehmen. Sobald die verbindlichen Angaben vorliegen, die drei Datensätze in `public/index.html` und `public/turniere/index.html` ergänzen. Bei den vier angekündigten Ranglistenturnieren 2027 sind Konkurrenzen und Fristen noch nicht veröffentlicht bzw. hier nicht verifiziert; Platzhalter bleiben sichtbar.



## November 2026: SUNBALL-Fristen und Original-Partnerliste

Anmeldeschluss/Auslosung jeweils zwei Tage vor Turnier: 27.12.2026 → 25.12.2026 um 18:00/19:00 Uhr; 30.01.2027 → 28.01.2027 um 18:00/19:00 Uhr; 06.02.2027 → 04.02.2027 um 18:00/19:00 Uhr.

Die ursprüngliche Partnerliste wurde aus der vom Betreiber bereitgestellten Datei `Partner – tennis-schiri.de.htm` (HTML-Export der WordPress-Partnerseite) extrahiert und in `public/partner/index.html` übernommen, in genau dieser Reihenfolge:

1. Precise Resort Bad Saarow — https://www.precisehotels.com/bad-saarow
2. Sunball Tenniscenter Bad Saarow — https://www.sunballtennisbsa.de/
3. Sunball Tennis — https://sunball-tennis.de/
4. BLACK SEA GAMES — https://blackseagames.eu/
5. Junior World Tennis Series (JWTS) — https://www.jwtstennis.com/
6. RAJ Tennisversand — https://www.raj-tennisversand.de/
7. myscore.live — https://myscore.live/

**Original-Logos vollständig übernommen:** Der Benutzer stellte den Ordner `Partner – tennis-schiri.de_files.zip` bereit. Daraus wurden genau die sieben auf der ursprünglichen Partnerseite verwendeten Grafiken extrahiert (die CMYK-JPEG-Datei von Precise Resort zur Browserkompatibilität nach RGB konvertiert), als lokale Dateien unter `public/assets/partner/` ins Repository übertragen und in `public/partner/index.html` eingebunden. Sie werden durch den manuellen FTPS-Workflow hochgeladen. Die Original-Partnerlinks bleiben unverändert.

