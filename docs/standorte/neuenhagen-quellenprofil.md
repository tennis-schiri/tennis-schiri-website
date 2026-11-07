# Standortprofil – Gartenstadt Neuenhagen / NTC

**Status:** Quellenauswertung für die vorhandene Standortdatenbank `bo_venues`, **noch nicht als aktuelle Stammdaten bestätigt**  
**Quelle:** [Infos LK NTC 2026](https://docs.google.com/document/d/1L9r2NXpGIO1GZAFt0LuBth0pLxzHSCMAcmczVYIigT0/edit)  
**Auswertung:** 07.11.2026  
**Datensatz-Vorschlag:** `neuenhagen` – diese ID existiert bereits im zentralen Adminbereich.

## Vermutlich wiederverwendbare Standortangaben (vor Produktivübernahme prüfen)

| Kategorie | Quelle / Vorschlag |
| --- | --- |
| Name | Gartenstadt Neuenhagen / Tennisanlage NTC (offiziellen Standortnamen prüfen) |
| Parken | Parkplätze an Tennisanlage und Halle. Unmittelbar vor Halle parallel zur Hallenfront parken. Nicht auf dem Grünstreifen vor der Anlage parken; mögliche Kontrollen/Verwarnungen. |
| ÖPNV | S-Bahnhof Neuenhagen (S5); laut früherer Mail etwa 15 Minuten zu Fuß. |
| Umkleiden | Zugang zu Umkleiden, Duschen und Toiletten rechts am Clubhaus. |
| Clubhaus | Vor Betreten des Gastronomie-/Clubhausbereichs Sandplatzschuhe reinigen, ausziehen oder wechseln. |
| Gastronomie | „DUE FRATELLI“, italienische Küche; historische Website-Angabe: https://www.due-fratelli-neuenhagen.de/ (vor Übernahme auf Erreichbarkeit/Aktualität prüfen). |
| Kontakt früherer Mail | ntc93@tennis-schiri.de (nur als standortbezogene Alternative; organisatorische Mail-Absenderadresse ist künftig turniere@tennis-schiri.de). |
| Belag bei Hallenmatches | Advantage Red Court; früherer Hinweis: saubere Sandplatzschuhe geeignet (prüfen, welche Courts aktuell eingesetzt werden). |

**Noch offen:** offizielle vollständige Straßenadresse, genauer Navigationslink, aktuelle Platzanzahl und Außenplatzbelag, Barrierefreiheit, derzeitige Öffnungs-/Zahlungsbedingungen der Gastronomie, verantwortlicher Ansprechpartner / Telefonnummer.

## Wechselnde, NICHT dauerhaft zu speichernde Angaben

- Turnierleitung: in einer alten Variante **neben dem Clubhauseingang**, in einer anderen **im Zelt gegenüber dem Clubhaus**. Immer pro Turnier bestätigen.
- Ankunft: früher regulär **30 Minuten** vor Match; teilweise **15 Minuten** bei den ersten Matches um 08:30 oder 10:00. Einzelfall, nicht pauschaler Standortwert.
- Spielzeit: früher 08:30 bis möglicherweise 20:00 Uhr, nicht allgemein gültig.
- Hallenbelegung: abhängig von Turnier, Wetter, Tageszeit und Verzögerungen.
- Historische Nenngelder: **40 € pro Doppelteam** in einer Mail, **40 € Erwachsene / 30 € Jugend** in einer anderen. Deshalb Preis ausschließlich pro Turnier/Konkurrenz.
- Historische Zahlungsmittel bei Turnierleitung: bar, Karte, Apple Pay, PayPal; müssen je Turnier geprüft werden. Keine automatische Aufforderung zu „Freunde & Familie“.
- Historische Gastronomie-Angaben: Öffnung ungefähr 11 Uhr, keine Kartenzahlung. Keine zeitlose Zusicherung.
- Auslosungsstatus, erlaubte Änderungen, Folgen von Nichtantreten und konkrete LK-Strafpunkte ausschließlich nach aktueller Ausschreibung und Regelwerk.
- Spezifische Datumsangaben (23. Mai sowie 25./26. April 2026) keinesfalls als Standortdaten speichern.

## Empfohlene Zuordnung zum bereits existierenden Adminprofil

Vorhandene `boVenueDefinitions()` in `public/admin/venues.php`:
- `address`: **leer lassen**, bis genaue Anschrift belegt.
- `navigation`: **leer lassen**, bis konkreter Ziel-Link belegt.
- `surface`: nur nach Bestätigung, ansonsten standort-/platzbezogen pflegen.
- `checkin`: **leer/turnierspezifisch**, da Quellen widersprüchliche Standorte nennen.
- `parking`: Parkhinweise von oben als Entwurf nach Bestätigung.
- `catering`: Due Fratelli mit vorbehaltlichen Öffnungs-/Zahlungsinformationen.
- `facilities`: Umkleiden, Duschen, Toiletten rechts am Clubhaus; Schuhhinweis.
- `contact`: konkrete Kontaktfreigabe prüfen.

**Keine schreibende Übernahme in die Live-SQLite vor Validierung.** Der parallel entwickelte zentrale Adminbereich und seine `bo_venues`-Struktur bleiben unverändert.

## Geplanter Zusatz für das Datenmodell

Es wäre hilfreich, pro Standortfeld ein `verified_at` bzw. Quellen-/Prüfdatum sowie eine Unterscheidung „öffentlich in Mail“, „intern“ und „nur pro Turnier“ vorzusehen. Dadurch werden vorläufige Altangaben nicht ohne Prüfung in neu erstellte Turnier-Guides übernommen.


## Zusatzquelle: Hallenturnier NTC93 vom 01.11.2025

[Infos LK Halle NTC93](https://docs.google.com/document/d/1WOueQc-LnPHRrLST6X4y1ZfKvEnAdwzK8i3ENQlJ1KA/edit)

Diese Quelle bestätigt die bereits beschriebenen Parkplatz-, S-Bahn- und Gastronomiehinweise. Sie liefert wichtige **hallenspezifische** Ergänzungen:

| Feld | Historische Angabe / Zuordnung |
| --- | --- |
| Hallenturnierleitung | Vorraum der Halle – nicht als allgemeiner Standort der Turnierleitung verwenden |
| Sanitärbereich | Umkleiden, Duschen und Toiletten sowohl in der Halle als auch im Clubhaus |
| Hallenplätze | Zwei Plätze beim beschriebenen Hallenturnier; aktuelle Anzahl prüfen |
| Hallenbelag | Advantage Red Court; saubere Sandplatzschuhe wurden zugelassen |
| Halleneingang | Während des Turniers elektronisch entriegelt; technische Zugangsdetails nicht in öffentlichen oder GitHub-Standortprofilen hinterlegen |
| Anfahrt | Wie in der vorherigen Quelle: Parkplätze an Anlage/Halle, parallel vor Hallenfront, Grünstreifen freihalten, S5 Neuenhagen ca. 15 Minuten Fußweg |
| Gastronomie | Due Fratelli; damals Öffnung ab 12:00 Uhr, damals ausschließlich Barzahlung – **turnier- und zeitabhängig**, nicht als dauerhafte Garantie übernehmen |

### Nur zum damaligen Doppel-LK-Turnier gehörende Angaben

- Anwesenheit 30 Minuten vor Match, bei den ersten 10:00-Uhr-Spielen 15 Minuten.
- Einspielen fünf Minuten; Ergebnis direkt melden.
- Zwei Gewinnsätze, dritter Satz Match-Tiebreak und No-Ad-Regel.
- Damaliges Nenngeld 60 € je Team, damalige Zahlungsoptionen bar/Karte/PayPal.
- Hinweis, am Vorabend die Ansetzungen erneut zu kontrollieren.
- Auslosung, mögliche Änderungen und Sanktionshinweise: vor jeder zukünftigen Mail neu prüfen.

### Konsequenz für die Datenbank

Die bestehende Standort-ID `neuenhagen` kann als übergeordneter Ort erhalten bleiben; sinnvoll sind darunter separate Anlagen-/Platzprofile **Halle** und **Außenanlage**. Damit werden wechselnde Angaben zu Belag, Platzanzahl, Anmeldung und Umkleiden nicht vermischt.

**Sicherheitsgrenze:** Die historische Quelle enthält eine technische Zugangsinformation. Solche Zugangsdaten gehören weder in das öffentliche Website-Repository noch in automatisch wiederverwendete E-Mail-Vorlagen. Sie wurden bewusst nicht übernommen.
