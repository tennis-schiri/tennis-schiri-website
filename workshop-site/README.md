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

## Überarbeitete Web-Folien: Design und Originalinhalte

Die 136 Webfolien nutzen im Standardmodus **keine statischen Vollseitenbilder** mehr. Titel, Kernaussagen, Fragen, Auflösungen und Video-Player werden als echtes HTML einheitlich dunkelgrün gestaltet. Texte und Vektorgrafiken bleiben auf Projektoren, Tablets und Smartphones scharf. Der bisherige Bildsatz ist vollständig erhalten und über **„Originalfolie ansehen“** erreichbar.

- **Folie 014:** Vektorbasierte Tennisplatzgrafik mit Einzel-/Doppellinien und korrekt ausgerichtetem Netz.
- **Folie 015:** Neue Netzgrafik mit klarer Absenkung in der Mitte. **91,4 cm Netzhöhe** und **91,4 cm Abstand der Einzelstützen außerhalb der Einzel-Seitenlinien** sind zwei unterschiedliche Maße. Die Einzelstützen stützen das Netz auf **1,07 m**.
- **Folien 001, 003, 004, 120 und 123:** Großflächige Original-Tennisfotos.
- **Originalmaterial:** Die Quellenzuordnungen decken alle 97 Folien der ursprünglichen PowerPoint ab. Zusätzliche Originaldetails stehen unter **„Mehr aus dem Original“**. Auf Fragefolien werden Auflösungen nicht vorweggenommen.

## Teilnahme auf Smartphone und Tablet

- Teilnehmerseite: https://workshop.tennis-schiri.de/teilnehmen.php
- Referentenansicht: https://workshop.tennis-schiri.de/presentation.php
- Der Referent startet mit **„● Live starten“** einen eigenen Raum, erhält einen **sechsstelligen Raumcode** sowie einen kopierbaren Teilnahmelink.
- Teilnehmer melden sich mit dem vorhandenen Workshop-Passwort an und geben den Raumcode ein. Sie sehen die aktuelle Folie nach maximal etwa **2,3 Sekunden** Abfrageintervall.
- Mit „Live folgen“ lässt sich die Synchronisierung pausieren oder wieder aufnehmen. Eigenständiges Blättern bleibt möglich.
- Bei den 27 Regelfragen kann über **A/B/C** abgestimmt werden. Pro Browser-Sitzung wird eine Stimme je Frage und Raum gezählt. Die Summe ist auf der Referentenfolie sichtbar.
- Der Presenter steuert den Raum mit einem sessiongebundenen Host-Token, Teilnehmer können ihn nicht weiterschalten.
- Alle Inhalte können auch ohne Raum im eigenen Tempo gelesen und die Videos abgespielt werden.

Die Live-Daten werden ohne externen Dienst über PHP und geschützte JSON-Dateien im Serververzeichnis _private/live-state/ abgewickelt. Es werden dabei keine Teilnehmernamen gespeichert. Raumstände laufen nach spätestens acht Stunden Inaktivität ab.

## Videoqualität

Alle 25 Webvideos sind direkt per HTML5-Player abspielbar, inklusive Pausen- und Spulfunktion. Die MP4s verwenden H.264; 24 enthalten AAC-Ton, ein Originalclip hat keine Tonspur. PHP unterstützt HTTP-Byte-Ranges für Mobilgeräte.

**Qualitätseinschränkung:** Die derzeitige kompakte Webfassung hat nur rund **320 Pixel Videobreite**. Sie ist kompatibel, aber nicht hochauflösend. Optional bevorzugt das Backend Originalvideos im geschützten FTP-Unterordner _private/media-hq/ (Dateinamen clip-107.mp4 bis clip-131.mp4). Ohne diese zusätzlichen Originaldateien werden weiterhin die kompakten Clips abgespielt; der GitHub-Workflow löscht bereits hochgeladene HD-Videos nicht.

Browser- und Mehrgerätetests nach dem manuellen Live-Upload sind erforderlich. Auch die Aktualität der Regelaussagen, besonders bei TVBB-Saisonbestimmungen, muss fachlich überprüft werden.

## Bildfüllende Folien mit fließendem Text (Version IM02)

Bei **bildbasierten Folien** wird die Illustration bzw. das Originalfoto nun als vollflächiger Hintergrund eingesetzt. Überschrift, Originalangaben und Bedienelemente liegen **darüber**, eingebettet in einen weichen Verlauf. Das bisherige Tennis-Schiri-Grunddesign bleibt mit Dunkelgrün, Lime-Akzenten und klarer Navigation erhalten. Auf Smartphone/Tablet steht das Bild im oberen Bereich, darunter fließt der Text auf einem kontrastreichen Hintergrund weiter. Die ursprüngliche Folie bleibt über **„Originalfolie ansehen“** abrufbar.

**Keine mehrfach verwendeten Bildhintergründe:** Die Bildzuordnung in `workshop-slide.js` ist explizit und wird beim Deployment auf Dubletten kontrolliert.

| Folie | Eigenständiges Bildmotiv | Darstellung |
|---|---|---|
| 3 | Tennisaktionsfoto des Referenten | echtes Foto, bildfüllend |
| 4 | zweites Tennisfoto des Referenten | echtes Foto, bildfüllend |
| 5 | HC02 – Jeu de Paume | Comic-Illustration |
| 6 | Jeu-de-Paume-Originalzeichnung | historische Quelle |
| 7 | Wingfield-Porträt | historische Quelle |
| 10 | Wimbledon 1877 | historische Quelle |
| 11 | HC01 – Lawn Tennis 1887 | Comic-Illustration |
| 12 | farbiges Lawn-Tennis-Bild 1887 | historische Quelle |
| 120 | drittes Tennisfoto des Referenten | echtes Foto, bildfüllend |

Die historischen Originale stammen aus dem Drive-Ordner **Regelkurs** und liegen im Repository unter `workshop-site/history-originals/`. Sie werden beim Deployment nach `_private/history/` kopiert und ausschließlich mit gültiger Workshop-Session per `resource.php` ausgeliefert. Die zwei bereits erstellten Comicbilder HC01 und HC02 werden aus den überprüften GitHub-Binärteilen erzeugt und ebenso geschützt ausgeliefert. Ihre Kennzeichnung **„Comic-Interpretation · kein Originalfoto“** wird sichtbar über dem Bild eingeblendet.

**Historische Gestaltung:** Georgia-/Serif-Schrift für Titel und Inhalt, warme Creme-, Sepia- und Goldakzente statt der sonst üblichen Lime-Typografie. Historische Quellen und Comic-Interpretationen werden bewusst unterschieden. Folie 8 (Video) und Folie 9 (Textthema zur Zählweise) bleiben ohne wiederholtes Hintergrundmotiv, nutzen aber die warme historische Typografie.

**Bewusste Ausnahme:** technische Netzhöhen, Platzmaße, Ausrüstungsgrafiken, Regelfragen und Videos bekommen kein beliebiges dekoratives Vollbildfoto, damit Maße, Interaktionen und Play-Bedienelemente lesbar bleiben.

Die Website bleibt passwortgeschützt, alle **136 Folien**, **27 Frage-/Antwort-Paare** und **25 Videofolien** bleiben erhalten. Der manuelle Upload und der anschließende Mehrgerätetest sind weiterhin erforderlich.

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
