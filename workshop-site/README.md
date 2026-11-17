# Tennis-Schiri · Regelworkshop-Webpräsentation

Separate Web-Präsentation für https://workshop.tennis-schiri.de – unabhängig von der Hauptwebsite.

**Aktueller Stand:** interaktive **Demo** mit 17 Ansichten, sechs Quizfragen, Kapitelwahl, Vollbild und Schwarzblende. Die vollständige PowerPoint mit 136 Folien bleibt separat. Originalvideos sind aus urheberrechtlichen Gründen nicht in der öffentlichen Demo enthalten.

**Fernbedienung:** Rechts / Bild ab / Leertaste = weiter bzw. Antwort aufdecken; Links / Bild auf = zurück; F = Vollbild; G = Kapitelwahl; B = Schwarzblende; H = Hilfe; Pos1 / Ende = erster / letzter Bildschirm.

## ALL-INKL und GitHub vorbereiten

1. Die Subdomain muss auf `/workshop-tennis-schiri-de/` zeigen.
2. Einen **separaten FTP-Benutzer** in ALL-INKL anlegen, dessen Startverzeichnis auf **genau diesen Ordner** begrenzt ist. Wenn dieser Benutzer per FTP angemeldet ist, entspricht das Startverzeichnis `/`.
3. Im Repository unter **Settings → Secrets and variables → Actions** folgende Repository Secrets einrichten:
   - `FTP_WORKSHOP_HOST` – FTP-Hostname
   - `FTP_WORKSHOP_USER` – separater FTP-Benutzer
   - `FTP_WORKSHOP_PASSWORD` – dessen Passwort
4. [WORKSHOP – Website manuell live hochladen](https://github.com/tennis-schiri/tennis-schiri-website/actions/workflows/deploy-workshop.yml) öffnen und **Run workflow** wählen.

Keine Passwörter in Chat oder Repository schreiben. Der Workflow lädt ausschließlich `workshop-site/index.html`, `workshop-site/robots.txt` und das freigestellte TS09c-Logo aus `branding/` hoch. Er löscht keine Dateien auf dem Webspace.

Die Demo hat eine `noindex`-Anweisung, **ist damit aber nicht passwortgeschützt**. Vor einer öffentlichen Nutzung alle Regelinhalte und Mediendateien rechtlich und fachlich freigeben.

© Tennis-Schiri · TS-WORKSHOP-2026
