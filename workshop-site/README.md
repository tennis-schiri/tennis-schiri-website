# Tennis-Schiri · Workshop-Website

Stand: 17. November 2026. Die Subdomain erhält eine öffentliche Startseite und eine serverseitig passwortgeschützte Präsentation mit 136 Folien und 25 Videos.

## Seiten

- Öffentliche Startseite: https://workshop.tennis-schiri.de/
- Passwort-Login: https://workshop.tennis-schiri.de/login.php
- Geschützte Präsentation: https://workshop.tennis-schiri.de/presentation.php
- Logout: https://workshop.tennis-schiri.de/logout.php

Die Startseite liegt in workshop-site/startseite.html. Der originale Präsentations-Quelltext bleibt als workshop-site/index.html im Repository und wird beim Deployment ausschließlich unter dem gesperrten Server-Verzeichnis /_private/index.html veröffentlicht. Folien, Referentennotizen und Videos werden nur mit gültiger Session über resource.php ausgeliefert.

Der Link zur Hauptwebsite https://tennis-schiri.de/ steht auf der öffentlichen Startseite ausschließlich unten im Bereich Backend.

## Neues GitHub-Secret

Zusätzlich zu den drei bereits vorhandenen FTPS-Secrets (FTP_WORKSHOP_HOST, FTP_WORKSHOP_USER, FTP_WORKSHOP_PASSWORD) wird ein weiteres Secret benötigt:

WORKSHOP_ACCESS_PASSWORD — frei zu wählendes Workshop-Passwort mit mindestens 12 Zeichen.

Hier hinterlegen: https://github.com/tennis-schiri/tennis-schiri-website/settings/secrets/actions

Das Passwort niemals im Chat, im Repository oder als normale Datei speichern. Der Workflow erzeugt beim Deployment einen PHP-Passwort-Hash, der nur im gesperrten Serververzeichnis liegt.

## Manueller Upload

https://github.com/tennis-schiri/tennis-schiri-website/actions/workflows/deploy-workshop.yml

Erst nach dem neuen Secret über Run workflow (main) starten. Der Workflow prüft die ZIP-Prüfsumme der 11 Paket-Teile, stellt die 136 Folien und 25 Clips in /_private/ bereit, generiert den Passwort-Hash und lädt zuerst die Apache-Schutzregeln ins ALL-INKL-FTP-Verzeichnis /workshop-tennis-schiri-de/. Danach prüft er per HTTP, dass Bilder und Videos ohne Anmeldung gesperrt sind.

Der separate ALL-INKL-FTP-User muss genau auf den Workshop-Ordner beschränkt sein. Die Hauptwebsite bleibt unangetastet.

## Steuerung

Pfeiltasten/Bild auf-ab/Leertaste für Folien, G Kapitel, S Suche, N Notizen, P Video, F Vollbild, B Schwarzblende. Das konkrete USB-Presenter-Modell vorher testen.

## Sicherheitsgrenze und Urheberrecht

**Wichtig:** Das Repository tennis-schiri/tennis-schiri-website ist öffentlich. Die Workshop-Bundle-Dateien und ältere GitHub-Commits bleiben damit auch unabhängig von der passwortgeschützten Subdomain einsehbar. Für echte Vertraulichkeit müsste das Material in ein privates Repository umziehen und aus der öffentlichen Historie verschwinden. Browser-Kopien, die schon bei Nutzern liegen, können nicht nachträglich zurückgeholt werden.

Video-/Bildrechte und Aktualität der ITF-, DTB- und TVBB-Regelhinweise müssen vor öffentlicher Nutzung geprüft werden.

© Tennis-Schiri · TS-WORKSHOP-2026.


## Redaktionelle Reihenfolge (Web-Präsentation)

Die Vortragsfolge wurde nach inhaltlicher Prüfung der vollständigen 136 Folien umgestellt, ohne Inhalte oder Medien zu entfernen. Quizfragen und Antworten bleiben stets direkt nacheinander.

- Geschichte: Jeu-de-Paume-Video direkt im historischen Abschnitt (neue Folie 8).
- Regelfälle: Ballkontakt-Videos direkt nach den Ballkontaktfragen (56–57).
- Schlägerwurf: drei Beispielvideos direkt nach der Frage (60–62).
- Netzberührung: vier Videobeispiele beim betreffenden Regelfall (67–70).
- Spielen über/neben dem Netz: vier weitere Beispiele beim passenden Fall (73–76).
- Elf nicht eindeutig beschriftete Bonusvideos: als **optionales Videolabor** vor Quellen und Abschluss.
- Quellen-/Prüffolie: vor der abschließenden Dankesfolie.
- Der Schluss „Danke für eure Aufmerksamkeit“ ist die letzte Folie (136).

Die Original-Bild- und Videodateien behalten ihre Dateinamen und Herkunftsnummern. Das Skript `editorial_order.py` stellt die richtige Zuordnung beim Upload her und passt die sichtbare Web-Foliennummer an. Die frühere ZIP-Downloadfassung und die lokale PowerPoint bleiben unverändert; eine Umstellung dieser Dateien benötigt jeweils eine gesonderte neue Ausgabe.

**Live-Upload bleibt ausschließlich manuell:** https://github.com/tennis-schiri/tennis-schiri-website/actions/workflows/deploy-workshop.yml



## Didaktisch überarbeitete Vollfassung (18.11.2026)

Der neue Build wurde aus dem Originalmaterial reorganisiert. **Keine der 136 Folien, 27 Frage-/Auflösungs-Paare oder 25 Originalvideos wird entfernt.** Die Reihenfolge von Folienbild, interner Foliennummer, Referentennotiz, Kapiteltitel und Suche wird gemeinsam aus dem Originalpaket neu aufgebaut. Die auf Folienbildern gedruckten Nummern und Kapitellabels werden passend aktualisiert.

Die Kapitel sind nun:

1. Einstieg und Vorstellung (1–4)
2. Tennisgeschichte, mit historischem Clip (5–12)
3. Spielfeld und Netz (13–17)
4. Schläger und Besaitung (18–23)
5. Zählweise und Tie-Break (24–32)
6. Matchablauf, Wahl, Seitenwechsel und Fehlerkorrekturen (33–42)
7. Spiel ohne Schiedsrichter mit Ballabdruck-, Ausruf- und Spielstandfällen (43–60)
8. Weitere Regelfälle, **mit Videos direkt beim passenden Thema** (61–93)
9. TVBB: Melde- und Spielberechtigung, OSR, Coaching, Doppel, Wetter (94–118)
10. Regelwerke / Aktualitätscheck (119)
11. Fazit und Dank (120–123)
12. Optionales Bonusarchiv mit alten nuLiga-Ansichten und weiteren Videos (124–136)

Die Abschlussfolie ist **123**. Der normale Vorwärts-Presenter stoppt dort; die 13 optionalen Archivfolien sind über **Kapitel → BONUS · ARCHIV** erreichbar. Das Archiv bleibt bewusst außerhalb des durchgehenden Vortrags. Alle Q/A-Paare sind weiterhin unmittelbar benachbart.

`workshop-site/reorder.py` ist das Quellskript für die Reihenfolge. Der Workflow rekonstruiert das unveränderte Originalpaket und baut beim manuellen Deploy die neu beschrifteten Folienbilder samt konsistenten Metadaten. Der Workflow nutzt dazu Pillow und überprüft die Anzahl von Folien, Videos und den Passwortschutz. Das alte `editorial_order.py` wird nicht mehr ausgeführt.

Der getrennte Passwortschutz, die Startseite und die drei FTP-Secrets plus `WORKSHOP_ACCESS_PASSWORD` bleiben unverändert.
