# Tennis-Schiri – Turnier-Mail-Konfigurator und Turnier-Guide 2.2

**Status:** abgestimmte Produktspezifikation, noch nicht implementiert  
**Stand:** 07.11.2026  
**Bereich:** zentraler Tennis-Schiri-Adminbereich → Turnier-Mails  
**Absender / Reply-To:** `turniere@tennis-schiri.de`

## 1. Ziel und Abgrenzung

Organisatorische, an Teilnehmer konkreter Turniere adressierte Info-Mails werden im Tennis-Schiri-Adminbereich vorbereitet, geprüft und versendet. Kein nuLiga-Versand und keine Vermischung mit Newsletter-Abonnenten oder deren Einwilligungen. Vorhandene PHP-/SQLite-Mail-Infrastruktur grundsätzlich wiederverwenden, aber Versandhistorie und Teilnehmerlisten je Turnier getrennt halten.

**Wichtig für parallele Adminentwicklung:** Diese Datei beschreibt Anforderungen. Keine bestehenden Adminrouten, Sessions, Datenbanktabellen oder Deploy-Workflows ohne Abgleich mit der parallel entstehenden zentralen Administration überschreiben. Aktueller separater Prototyp: `public/turnier-mails.php`; ältere Setup-Notizen: `TURNIER-MAILS-SETUP.md`. Der Prototyp ist *nicht* bereits die Umsetzung dieser Spezifikation.

## 2. Designsystem

An vorhandenes Newsletter-Design angleichen, jedoch deutlich stärker als **digitaler, sportlicher Turnier-Guide** gestalten:

- offizielles TS09c-Logo (korrigierte transparente Variante), gleiche Marke wie Website/Newsletter;
- dunkles Tennis-Schiri-Grün ca. `#143B2C`, Limetten-Akzent `#C5F17F` bzw. bestehende Newsletter-Farbe `#C2EE7B`, sehr helle Flächen `#F0F6EA`;
- Turnier-Hero mit großem Titel und eigenständiger Datum-/Ort-Karte;
- große Zahlen für zentrale Fakten: „30 Min. vorher“, „5 Min. Einspielen“ (Werte konfigurierbar);
- Preisübersicht als saubere Tabelle, Anreise-/Digitalservice-Karten, wichtige Regeln als eigener Warn-/Hinweisbereich;
- kein großer Fließtextblock: kurze Texte, klare Zwischenüberschriften, visuelle Hierarchie;
- responsive **E-Mail-kompatibles Tabellen-HTML**, Inline-CSS, saubere Plaintext-Version, Bild-Alt-Texte, keine Skripte/Tracking-Pixel, Links im Text erkennbar;
- die gestalterischen Mini-Labels dürfen sportlich wirken, der relevante Inhalt bleibt deutsch;
- Partnerwerbung/TENNILO nicht automatisch in obligatorische Teilnehmer-Informationen aufnehmen.

## 3. Informationsarchitektur (Teilnehmerperspektive)

Die finale Mail soll **nicht nach der Reihenfolge der Ausgangsnotizen**, sondern nach dem Ablauf der Teilnahme aufgebaut werden:

1. **Hero / Dein Turnier** – Name, Datum, Spielort, Konkurrenzen (falls relevant), prominenter Link zur offiziellen Auslosung / Spielplan auf tennis.de oder zur konkreten Turnierseite.
2. **Aktuelle wichtige Hinweise (optional)** – z. B. Heizung, kurzfristige Änderungen, Zufahrt; bei Aktivierung oben prominent.
3. **Bevor du losfährst** – angegebene Anwesenheitszeit (Standard 30 Min.), erforderliche Schuhe/Ausrüstung, kurzer Zahlungs-Hinweis, Ansetzungen prüfen.
4. **Anreise & Check-in** – vollständige Anschrift, Karten-/Navigationslink, Parkplatz, Einfahrt/Schranke, Treffpunkt der Turnierleitung, Ablauf der Anmeldung und Verspätungs-Kontakt.
5. **Digitale Turnier-Services (optional)** – Turnigo, myscore.live unabhängig voneinander; bei beiden eine gemeinsame kompakte Sektion mit zwei Karten statt zweier riesiger Werbeblöcke.
6. **Auf dem Court / Spielbetrieb** – Belag, Spielmodus/Zählweise, Bälle, Einspielzeit, Ergebnismeldung, Pausen, Zeitfenster, mögliche Wartezeiten, Ab-/Anmeldung beim vorübergehenden Verlassen.
7. **Nenngeld & Service** – Gebühren nach Konkurrenz inkl. etwaiger DTB-Gebühr/Verzehrbons; Zahlungsarten; Gastronomie, Gutscheinbedingungen, Umkleiden/Duschen als optionale Details. Oben nur eine kurze Zahlungs-Erinnerung, hier volle Beträge.
8. **Wichtige Regelungen** – Auslosungsstatus, Änderungen, Rücktritt/Nichtantreten, Nenngeldpflicht und mögliche LK-Folgen. Konkrete Formulierungen vor Versand anhand aktueller Ausschreibung und gültiger DTB-Regelungen prüfen; keine zeitlosen, unbestätigten Strafpunktebehauptungen.
9. **Abschluss & Kontakt** – freundlicher Gruß „Michael“, Antwortadresse `turniere@tennis-schiri.de`, Telefon für dringende Fälle falls angegeben, ggf. erneuter Spielplan-Link. Keine Aufforderung, auf eine funktionierende Reply-To-Adresse *nicht* zu antworten.

Doppelte Sachinformationen vermeiden. Hervorgehobene Kurzübersicht und ausführliche Bereiche dürfen denselben Sachverhalt unterschiedlich detailliert darstellen, aber keine widersprüchlichen Werte.

## 4. Checklisten-Konfigurator im Adminbereich

**Workflow:** Turnier wählen → Standortvorlage wählen → Mailtyp → Inhalte per Checkbox an-/abwählen → zu jedem aktiven Modul relevante Datenfelder anzeigen → Vorschau (Desktop/Mobil) → Testmail → Empfängerprüfung → ausdrückliche Freigabe → manueller Versand mit Status.

**Mailtypen:**
- ausführliche Information nach Auslosung (Standard),
- kurze Erinnerung vor Turniertag,
- wichtige Änderung / Sonderinformation.

**Gruppen mit auswählbaren Modulen:**

### A. Turnier & Grundinformationen
- [x] Turniername, Datum, Spielort **(Pflicht, nicht abwählbar)**
- [x] Link zur Auslosung / zum Spielplan
- [ ] Turnigo-Link
- [ ] myscore.live-Link
- [ ] Aktuelle Hinweise / Änderungen

### B. Anreise & Anmeldung
- [x] Anschrift und Navigation
- [x] Parkmöglichkeiten
- [x] Anwesenheit vor Spielbeginn
- [x] Anmeldung bei der Turnierleitung
- [x] Hinweise bei Verspätung

### C. Spielbetrieb
- [x] Belag und geeignete Schuhe
- [x] Spielmodus und Zählweise
- [x] Einspielzeit
- [x] Ergebnismeldung
- [x] Pausen und Wartezeiten
- [ ] Spielbälle / Ballmarke

### D. Nenngeld & Anlage
- [x] Nenngeld je Konkurrenz
- [x] Zahlungsarten
- [x] Gastronomie / Verzehrbons
- [ ] Umkleiden und Duschen

### E. Regeln & Kontakt
- [x] Auslosung und Änderungen
- [x] Abmeldung und Nichtantreten
- [x] Kontakt für Rückfragen **(Pflicht, nicht abwählbar)**
- [ ] Turnierfotos / Social Media (mit Rechtsprüfung)
- [ ] WhatsApp-Community (nur dezent und optional)

Aktivierungszustände sind **Vorlagen-Defaults**, keine festen Turnierfakten. Nur vorhandene und relevante Module tatsächlich anzeigen. Nicht freigegebene Werbeinhalte nicht ungefragt beifügen.

## 5. Standort- und Turniervorlagen

- Zunächst **Tennis Center Bad Saarow**, **Neuenhagen**, **allgemeine Vorlage**.
- Standortbezogene, wiederverwendbare Daten: Name, Straße/Hausnummer, Navigationslink, Belag, Turnierleitungs-Standort, Park-/Schranken-/Ausfahrt-Hinweise, Gastronomie, Umkleiden, Standardkontakt.
- Turnierbezogene Daten: Name, Datum/Uhrzeit, Konkurrenzen, Spielplan-URL, Nenngeld je Konkurrenz, erlaubte Zahlarten, Zählweise, Einspielzeit, Besonderheiten, konkrete Live-Service-Links.
- Vorlagenänderungen dürfen freigegebene oder bereits versendete Mails **nicht stillschweigend ändern**; Snapshot beim Freigeben.
- Wenn möglich Turniername/Termin/Ort/Website-Link aus dem veröffentlichten Tennis-Schiri-Turnierkatalog vorausfüllen; keine unbestätigten Daten erfinden.

### Vorhandene Bad-Saarow-Ausgangswerte (bei neuer Mail prüfen!)

Aus den älteren Info-Mails, nicht blind als aktuelle Stammdaten verwenden: Hartplatz Laykold, Turnierleitung am Counter im Eingangsbereich, drei Plätze, Einspielzeit fünf Minuten, Ankunft 30 Minuten vor Match, kostenlose Parkplätze hinter Schranke, Ausfahrt-Ticket bei Turnierleitung/Gastronomie, Feuerwehrzufahrt/Mitarbeiterstellplätze freihalten, Gastronomie mit Verzehrbon. Historisch erwähnte Beträge: Jugend 45 €, Erwachsene 55 €, Mixed 60 €, jeweils inklusive DTB-Gebühr und nach Konkurrenz einem oder zwei Verzehrbons. Historisch erwähnte Zahlungsarten: bar, Karte, Apple Pay, PayPal. **Alle Preise, Zahlungsbedingungen und Sonderhinweise vor dem jeweiligen Versand verifizieren.** Eine pauschale PayPal-„Freunde & Familie“-Aufforderung nicht automatisch übernehmen.

## 6. Digitale Services

### Turnigo (`https://www.turnigo.de/`)
- eigene Checkbox „Turnigo – digitaler Turnierbegleiter“;
- Pflichtfeld für **individuelle Turnier-URL**, wenn aktiviert; kein bloßer Link auf die Anbieter-Homepage;
- kurze funktionale Erklärung zu den *beim betreffenden Turnier wirklich verfügbaren* Leistungen (z. B. Zeit-/Platzinfos, QR-/Check-in); keine Funktionen ohne Prüfung als garantiert ausgeben;
- ggf. Hinweis zum QR-Code vor Ort nur bei zutreffender Turnigo-Konfiguration.

### myscore.live (`https://myscore.live/`)
- eigene Checkbox „myscore.live – Live-Spielstände“;
- Pflichtfeld für **individuelle Live-URL**, wenn aktiviert;
- kurze Erklärung: Spielstände für Teilnehmer und Zuschauer mobil verfolgen, sofern beim Turnier eingesetzt;
- eigenständiger Link/CTA; zusammen mit Turnigo kompakte Doppelkarte unter „Digital am Turniertag“;
- keine Vermischung von offizieller Auslosung, Turnigo-Organisation und Live-Scoring.

## 7. Teilnehmer, Versand und Sicherheit

- CSV-/Excel-Import, falls technisch unterstützt, sonst CSV; E-Mail-Feld erkennen, Adressen validieren, Duplikate deduplizieren, Anzahl und fehlerhafte Zeilen vor Import anzeigen.
- Pro Turnier nur legitimierte Empfänger; **keine automatische Newsletter-Übernahme**.
- Adminauthentifizierung in die parallel entstehende **zentrale Administration integrieren**, keine dauerhafte zweite Anmeldung.
- Testmail an eigene Adresse, deutliche Testmarkierung, vorher Mobile-/Desktop-Vorschau.
- Separate Freigabe von Versand; Versand in kontrollierten Paketen unter Berücksichtigung der Hosterlimits.
- Versandstatus unterscheiden: ausstehend / vom Server angenommen / fehlgeschlagen / unklar; „angenommen“ ist keine garantierte Zustellung.
- Keine automatische Wiederholung bei unklarem Status (Doppelversandrisiko), keine öffentlichen Empfängerlisten, private Datenhaltung außerhalb des Webroots.
- Versand über `turniere@tennis-schiri.de`; der bestehende Prototyp nutzt PHP `mail()`. Postfach ist eingerichtet; SMTP, Authentifizierung, SPF/DKIM/DMARC und reale Zustellbarkeit bedürfen getrennter Prüfung.
- Turnier-Kommunikation von Marketing trennen; rechtliche Einordnung, Datenschutzhinweise und Speicherfristen vor Produktivnutzung klären.

## 8. Umsetzungsreihenfolge und Abnahmekriterien

1. Zentrale Adminschnittstelle und Zuständigkeiten mit dem anderen Entwicklungsstrang abgleichen; **keine parallelen Änderungen an denselben Adminseiten**.
2. Datenmodell: Standortvorlagen, Turniermetadaten, Modulzustände, Entwurf/Snapshot, Empfänger und Versandstatus.
3. Checkbox-Konfigurator mit Datenfeldern, Validierung, Speichern und Vorschau.
4. Responsives Turnier-Guide-HTML und Textalternative mit echtem TS09c-Logo.
5. Optionale Turnigo-/myscore-Karten samt individuellen gültigen HTTPS-Links.
6. Teilnehmerimport, Freigabe, Testversand, kontrollierte Pakete, Fehlerprotokoll.
7. Testfälle: ohne digitale Dienste, nur Turnigo, nur myscore, beide, unvollständiger Link, keine Empfänger, wechselnder Standort, Entwurf/Freigabe, Handy-Mailclient.
8. Nach erfolgreichem Integrationstest gesonderter manueller Live-Deploy und echte Testmails.

**Abnahmekriterium:** Ein Admin kann ohne Codeänderung für ein bestimmtes Turnier eine passende Mail aus Standortvorlage + aktiven Checklistenbausteinen erzeugen, die Vorschau prüfen, spezifische Links setzen, Test senden und anschließend mit protokollierter Freigabe an ausschließlich die zugehörigen Teilnehmer versenden.
