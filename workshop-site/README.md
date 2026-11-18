# Tennis-Schiri · Regelworkshop-Website

**Stand: 18. November 2026.** Die Webpräsentation entspricht der didaktisch neu geordneten PowerPoint-Vollfassung. Es gibt **136 Folien**, darunter **27 zusammengehörige Frage-/Auflösungs-Paare** und **25 Videofolien**. Die Videos werden in der kompakten Webfassung verwendet; die separate Master-PowerPoint enthält Originalvideos in höherer Qualität.

## Öffentliche Seite und Passwortschutz

- Startseite: https://workshop.tennis-schiri.de/
- Passwortzugang: https://workshop.tennis-schiri.de/login.php
- Präsentation nach Anmeldung: https://workshop.tennis-schiri.de/presentation.php
- Abmelden: https://workshop.tennis-schiri.de/logout.php

Die öffentliche Startseite präsentiert den Workshop. Ein Verweis auf https://tennis-schiri.de/ steht nur **unten im Footer unter „Backend“**. Die Vortragsfolien, Video-Dateien, Referentennotizen und Metadaten werden auf der Website ausschließlich nach gültiger PHP-Session über den serverseitigen Zugang bereitgestellt.

## Reihenfolge: identisch zur neu geordneten PowerPoint

| Webfolie | Kapitel |
|---|---|
| 1–4 | Einstieg, Agenda und Vorstellung |
| 5–12 | Geschichte |
| 13–17 | Tennisplatz |
| 18–23 | Tennisschläger |
| 24–32 | Zählweise und Tie-Break |
| 33–42 | Spiel- und Matchablauf |
| 43–60 | Spiel ohne Schiedsrichter, Linien und Ballabdrücke |
| 61–93 | Regelfälle und zugehörige Videos |
| 94–118 | TVBB-Mannschaftsspiele |
| 119 | Regelwerke und Aktualitätshinweise |
| 120–123 | Fazit und Abschluss |
| 124–136 | **Optionales Archiv** mit Zusatz- und Videomaterial |

**Wichtig:** Die normale Vorführung endet bei Folie **123**. Folie 124–136 sind über die Kapitelübersicht „Bonus · Archiv“ oder den Nummernsprung erreichbar, jedoch nicht durch versehentliches Weiterblättern nach der Abschlussfolie. Der Zähler im Hauptvortrag zeigt „… / 123“, im Archiv „BONUS … / 13“.

Der Build erhält alle 136 Folien, 25 Videos und Referentennotizen. Bilder, Kapitel, Suche und gedruckte Nummern werden aus demselben Folienkatalog erzeugt. Auf Fragen folgt unmittelbar die Auflösung.

## Technische Quellen und Build

- workshop-site/index.html: Quelldokument für die geschützte Präsentation, nicht öffentlich als solches bereitgestellt.
- workshop-site/bundle/part01 bis part11: 11 Teile des per SHA-256 kontrollierten kompakten Workshop-Medienpakets.
- workshop-site/reorder.py: **maßgebliche** Neuordnung, Neukennzeichnung der 136 Folienbilder und Metadaten.
- workshop-site/editorial_order.py: nur historischer Zwischenstand, **nicht verwenden**.
- workshop-site/startseite.html und access.css: öffentliche Landingpage und Login-Stil.
- workshop-site/security.php, login.php, resource.php, presentation.php, logout.php: PHP-Session und serverseitige Ressourcen-Gates.
- workshop-site/.htaccess und deny-private.htaccess: Apache-Schutz einschließlich alter direkter Medienpfade.

Der GitHub-Actions-Workflow richtet Python 3.12 + Pillow ein, prüft Archiv-Prüfsumme, 136 Folien und 25 Clips, erzeugt neue Bilder und Metadaten, aktualisiert die Browser-Asset-Version und führt statische JS-/PHP-Prüfungen durch.

## GitHub-Secrets

Die bekannten vier Repository-Secrets müssen vorhanden bleiben:

- FTP_WORKSHOP_HOST
- FTP_WORKSHOP_USER
- FTP_WORKSHOP_PASSWORD
- WORKSHOP_ACCESS_PASSWORD (mindestens 12 Zeichen; **niemals als Klartext in GitHub-Code oder Chat**)

Bearbeiten: https://github.com/tennis-schiri/tennis-schiri-website/settings/secrets/actions

## Manueller Live-Upload

https://github.com/tennis-schiri/tennis-schiri-website/actions/workflows/deploy-workshop.yml

GitHub Actions → Workflow → Run workflow → Branch main → Run workflow.

**Es findet kein automatischer Upload statt.** Der FTPS-Account muss im ALL-INKL-KAS auf /workshop-tennis-schiri-de/ eingeschränkt sein. Die Tennis-Schiri-Hauptwebsite bleibt unverändert. Nach Upload prüft der Workflow, dass Startseite/Passwort-Login erreichbar sind und Präsentation, Bilder und Videos anonym gesperrt sind.

## Original-Tennisfoto und separate Vollbild-Vorführung

Die öffentliche Startseite nutzt **das authentische Tennisaktionsfoto aus Google Drive** (bereits in der TS-Website als `public/assets/michael-wiedeck-spiel.jpg` vorhanden), statt der gezeichneten Tennisplatzgrafik. Der Build kopiert diese Originaldatei als `/assets/workshop-michael-tennis.jpg` in die Workshop-Subdomain. Das Markenlogo bleibt unverändert.

**Auch Folie 3 „Wer steht vor euch?“:** Beim Rendern der neu geordneten Web-Präsentation ersetzt `reorder.py` das alte Porträt vor gelber Wand im vorhandenen Bildfeld durch dasselbe echte Tennis-Aktionsfoto. Die daneben stehenden DTB-Qualifikationen und alle sonstigen Folienelemente bleiben erhalten. Der Fotoausschnitt wird für das Querformat der Karte angepasst.

In der passwortgeschützten Web-Präsentation führt die Taste **F** oder der Button **„⛶ Präsentieren“** in eine echte Vollbild-Vorführansicht. Dort werden Kopfzeile, Navigation und Rahmen ausgeblendet, und die 16:9-Folien sind so groß wie möglich. **Esc** oder der diskrete Button oben rechts beendet die Ansicht. Pfeile/Bild auf-ab/Leertaste funktionieren weiterhin; Kapitel (G), Suche (S) und Notizen (N) können auch im Vollbild aufgerufen werden.

Ein direkter Präsentationsaufruf nach Login ist unter `/presentation.php?present=1` möglich. Wegen Browser-Sicherheitsregeln muss man dort zunächst auf **„Präsentation starten“** klicken; ein ungefragter automatischer Fullscreen-Start ist nicht möglich. Auf Geräten ohne Fullscreen-API bleibt die bildschirmfüllende CSS-Vorführansicht verfügbar.

Der Presenter-JavaScript/CSS-Code liegt in `workshop-site/presenter-mode.js` und `presenter-mode.css` und wird vom Workshop-Workflow gemeinsam mit den 136 Folien hochgeladen. Der Zugriffsschutz wird dadurch nicht geändert.

## Presenter-Bedienung

- Rechts/Bild↓/Leertaste: weiter; Links/Bild↑: zurück.
- Home: erster Slide; End: Abschlussfolie **123**.
- G: Kapitelübersicht und Bonusarchiv.
- S oder /: Suche; N: Referentennotizen (auf dem Beamer sichtbar).
- P: Video starten/pausieren; F: Vollbild; B: Schwarzblende.
- Nummernzähler: zu beliebiger Folie springen, inklusive Archiv (124–136).

Das USB-Presenter-Modell auf dem späteren Veranstaltungsrechner testen. Bei veralteter Darstellung Browserseite neu laden; für den neuen Build sind CSS/JS-URLs versioniert.

## Grenzen und rechtliche Hinweise

Der Website-Passwortschutz ist serverseitig. Das GitHub-Repository ist aber **öffentlich**, weshalb die ZIP-Teile und frühere Commits weiterhin eingesehen werden können; wirklich vertrauliche Medien gehören in ein privates Repository. Bereits heruntergeladene Kopien können nicht rückwirkend gesperrt werden. Film-, Bildrechte und die Aktualität der ITF-/DTB-/TVBB-Regelaussagen vor jeder öffentlichen Veranstaltung prüfen.

© Tennis-Schiri · TS-WORKSHOP-2026.
