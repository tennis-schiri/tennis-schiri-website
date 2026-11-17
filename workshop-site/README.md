# Tennis-Schiri · Regelworkshop-Webpräsentation (Vollfassung)

**Stand: 17. November 2026**

Die Web-Präsentation wurde aus der neu gestalteten PowerPoint-Vollfassung erstellt. Sie enthält **136 Folien**, **27 gesonderte Antwort-/Auflösungsfolien**, **25 eingebettete Originalvideos** in komprimierter Form und die Referentennotizen aus der PowerPoint.

- Quellcode des Hauptdokuments: `workshop-site/index.html`
- Vollständiges Bild-, Script- und Medienpaket: `workshop-site/bundle/part01` bis `part11`
- Prüfsumme des durch Aneinanderhängen rekonstruierten ZIP: `958b6b2004c56f65e2967c03979b102e04346a0b40707662483618c16d09f8b6`
- Google-Drive-Ausgabe (ZIP, lokal nutzbar): https://drive.google.com/file/d/1Rm6_pAdcoCOwjdA5QBtsU3GBYSC7z-AJ/view

## Präsentation und Fernbedienung

- `→`, `Bild ab`, `Leertaste`: weiter / Quizauflösung auf nächster Folie.
- `←`, `Bild auf`: zurück.
- `F`: Vollbild.
- `G`: Kapitelübersicht.
- `S` oder `/`: Suche nach Begriffen und Folien.
- `N`: Referentennotizen anzeigen (auf dem Beamer sichtbar!).
- `B`: Bildschirm schwarz.
- `P`: Video abspielen oder pausieren.
- `Pos1` / `Ende`: erste/letzte Folie.
- `#folie=78`: gezielter Einstieg per URL.

Ein USB-Presenter muss die üblichen Tastaturbefehle senden. **Vor dem tatsächlichen Workshop testen.**

## Veröffentlichung

Die Subdomain `workshop.tennis-schiri.de` zeigt auf `/workshop-tennis-schiri-de/` im ALL-INKL-Webspace.

GitHub-Workflow: https://github.com/tennis-schiri/tennis-schiri-website/actions/workflows/deploy-workshop.yml

Benötigte GitHub-Actions-Secrets:
- `FTP_WORKSHOP_HOST`
- `FTP_WORKSHOP_USER`
- `FTP_WORKSHOP_PASSWORD`

**Wichtig:** Der separate FTP-Benutzer muss mit dem Startverzeichnis `/workshop-tennis-schiri-de/` eingerichtet sein. Der Workflow lädt nur die 169 Dateien der Workshop-Webpräsentation hoch. Die Hauptwebsite wird nicht verändert. Er wird **nur manuell** über den Button **Run workflow** gestartet.

## Vor der Veröffentlichung prüfen

Die Website ist durch `noindex` **nicht** gegen unbefugten Zugriff geschützt. Die Bilder und Videos aus der vorliegenden Präsentation werden auf einer öffentlich abrufbaren Subdomain veröffentlicht. Vor dem Upload sind Nutzungs-, Vorführ- und Weiterverbreitungsrechte an den Clips und Fremdbildern zu klären.

TVBB- und andere veranstaltungsbezogene Regelungen beziehen sich auf den Arbeitsstand der Vortragsunterlagen vom 20.03.2026. Vor der Vorführung gegen aktuelle ITF-, DTB- und TVBB-Regeln prüfen.

© Tennis-Schiri · TS-WORKSHOP-2026.
