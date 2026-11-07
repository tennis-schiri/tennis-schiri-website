# Ausbauplan tennis-schiri.de – Besucherfunktionen

Stand: 7. November 2026. Prioritäten vom Projektinhaber festgelegt. Die Reihenfolge ist verbindlich für den schrittweisen Ausbau; neue Unterseiten aufbauen, Startseite nicht überladen.

| Priorität | Modul | Status | Nächster Schritt / Abnahme |
| --- | --- | --- | --- |
| 1 | Mein erstes LK-Turnier | **Umgesetzt auf `main`** | `/lk-turnier/`: acht inhaltlich belegte Schritte, lokale interaktive Checkliste, Links auf Start- und Turnierseite; vor Livegang manuell deployen |
| 2 | Tennisanlagen und Veranstaltungsorte | Geplant | Je Ort Logo nur aus Originalquelle, Foto mit Nutzungsrecht, Adresse, Lage/Anfahrt, Belag und verknüpfte Turniere; vorhandene fünf Veranstaltungsorte weiterverwenden |
| 3 | Turnierrückblicke und Archiv | Geplant | Vergangene Turniere mit Ergebnis/Platzierung nur soweit offiziell veröffentlicht; Rückblicke und Foto-Galerien, Teilnehmerdatenschutz beachten |
| 4 | Kompletten Kalender abonnieren | Geplant | Abonnierbare ICS/Webcal-URL für Apple/Google/Outlook; neuer Termin und Änderung automatisch sichtbar; zuvor einzelne ICS-Dateien bereits verfügbar |
| 5 | Download-Center | Geplant | Kostenlos angebotene Checklisten, Aushänge, Vorlagen für Verein/Turnierleitung; aktuelle, gepflegte Dateiversionen |
| 6 | Tennis-Mythen | Geplant | Interaktive Wahr/Falsch-Karten mit Verweis auf offiziellen Paragraphen; wiederverwendbar für Instagram und Regelwissen |
| 7 | Heute auf dem Tennisplatz | Geplant | Nur bei laufenden Veranstaltungen: Zeitplan, Hinweise, Wetter und Ergebnisse; perspektivisch TENNILO-Anbindung, keine erfundenen Live-Daten |

## Technische Leitlinien

- Website: Repository `tennis-schiri/tennis-schiri-website`, Branch `main`.
- Für jede Website-Änderung den manuellen Workflow zum Live-Upload nennen: `https://github.com/tennis-schiri/tennis-schiri-website/actions/workflows/deploy-live.yml`.
- Änderungen auf `main` sind nicht automatisch live, sondern erst nach manuellem Workflow.
- Bestehenden Turnierkalender und seine Daten als Quelle wiederverwenden; keine konkurrierenden Datenpflege-Stellen anlegen.
- Eigene Landingpages statt immer mehr großer Startseiten-Abschnitte.
- Datenschutz und Rechte an Fotos/Logos berücksichtigen; bei unbekannten Daten offen kennzeichnen.
- Bei neuen Verzeichnissen mit `index.html` die Seitenzahl in Live-Workflow/Branding-Test und weiteren Abnahmetests mit aktualisieren.
- Für LK-Einstieg: DTB-Vorgaben zu Mitgliedschaft, DTB-ID, LK, tennis.de-Konto und kostenpflichtiger Spielerlizenz prüfen, insbesondere bei Regeländerungen.
