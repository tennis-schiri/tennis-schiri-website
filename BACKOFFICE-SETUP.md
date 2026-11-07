# Tennis-Schiri Backoffice – Einrichtung und Nutzung

Die zentrale Verwaltungsoberfläche für die Kommunikation und Turnierorganisation liegt unter **https://tennis-schiri.de/admin/**. Die Inhalte der öffentlichen Website werden weiterhin über GitHub gepflegt. **TENNILO bleibt getrennt.**

## Veröffentlichung und Login

1. In GitHub den [Live-Upload](https://github.com/tennis-schiri/tennis-schiri-website/actions/workflows/deploy-live.yml) über „Run workflow“ ausführen.
2. Nach erfolgreichem Workflow https://tennis-schiri.de/admin/ öffnen.
3. Mit dem bestehenden Newsletter-Admin-Passwort anmelden. Die bisherige Anmeldeseite übernimmt nach erfolgreicher Anmeldung die Weiterleitung zum Backoffice.
4. Die vorhandene Newsletter-Abonnentenverwaltung (/newsletter-admin.php) und der Newsletter-Versand (/newsletter-mail.php) bleiben erhalten und sind über das Backoffice verlinkt.

Das Backoffice verwendet die vorhandene HTTPS-Session, die nach acht Stunden abläuft. Kein zweites Passwort und kein neuer Webspace sind erforderlich.

## Funktionen

### Übersicht

Dashboard mit bestätigten Newsletter-Abonnenten, Turnierkontakten, Infomail-Entwürfen und noch ausstehenden Empfängerzustellungen. Zugriff auf die beiden vorhandenen Newsletter-Module sowie den neuen Infomail-Bereich.

### Teilnehmerverwaltung

Ein Turnier aus der bestehenden Tennis-Schiri-Terminübersicht wählen. Teilnehmer können einzeln erfasst oder als CSV importiert werden. Unterstützt werden eine Spalte „E-Mail“ / „Email“ / „E-Mail-Adresse“ und optional „Name“ bzw. „Vorname“ und „Nachname“. CSV-Dateien dürfen Semikolon oder Komma verwenden, maximal 1 MB und 500 Datenzeilen pro Import. Die Originaldatei wird nicht dauerhaft gespeichert.

Die E-Mail-Adressen werden ausschließlich dem jeweils gewählten Turnier zugeordnet, nicht dem Newsletter. Ein CSV-Import löst **keinen** Mailversand aus. Beim Entfernen eines Teilnehmers werden personenbezogene Kopien im zugehörigen Infomail-Protokoll anonymisiert und noch nicht versandte Mails für diesen Teilnehmer übersprungen.

### Turnier-Infomails

1. Turnier auswählen: Datum, Ort und der Turnierlink werden aus der Website übernommen.
2. Vorlage wählen: Teilnehmerinformation, Erinnerung oder organisatorische Änderung.
3. Betreff, Haupttext und optionale Zusatzinformationen (Meldezeit, Treffpunkt, Parken usw.) bearbeiten und speichern.
4. Über die **Checkliste** zusätzlich auswählen, welche Infos als eigene Karten erscheinen sollen: Meldezeit/Treffpunkt, Spielbeginn/Ablauf, Parken/Anfahrt, Nenngeld/Zahlung, Kontakt, Turnigo und MyScore.live. Die ergänzenden Texte und URLs werden im Entwurf gespeichert.
5. Vorschau öffnen, dann eine **Testmail nur an mw@tennis-schiri.de** schicken.
6. Empfängerliste prüfen. Mit der Eingabe **SENDEN** wird sie eingefroren. **Noch kein Versand.**
7. Anschließend den Versand bewusst in Chargen von maximal fünf Nachrichten pro Klick auslösen und die Einzelprotokolle prüfen.

Verfügbare Platzhalter: {{name}}, {{turnier}}, {{datum}}, {{ort}}, {{turnier_link}}, {{zusatz}}. Der letzte Platzhalter übernimmt das Feld „Zusätzliche Hinweise“.

Die optionalen Infoblöcke werden nur angezeigt, wenn das jeweilige Thema angehakt **und** sein Text oder Link hinterlegt ist. Für **Turnigo** wird ausschließlich eine HTTPS-Adresse auf turnigo.de oder einer passenden Subdomain akzeptiert. Für **MyScore.live** gilt dasselbe für myscore.live. Trage pro Turnier den tatsächlichen Link ein; es werden keine Anmelde- oder Ergebnislinks geraten. Die Auswahl ist nicht mit dem Newsletter-Opt-in verbunden und löst keinen automatischen Versand aus.

Die Checkliste hat eigene Datenbankfelder, die beim ersten Aufruf automatisch ergänzt werden. Bereits bestehende Entwürfe bleiben unverändert; neue Infoblöcke sind zunächst nicht aktiviert.

**Absender und Antwortadresse:** turniere@tennis-schiri.de. Der Newsletter nutzt weiterhin news@tennis-schiri.de.

Die Infomails sind für organisatorische Mitteilungen an bereits angemeldete Turnierteilnehmer gedacht, **nicht für Werbung**. Ein Versandstatus „vom Mailserver angenommen“ bestätigt keine erfolgreiche Zustellung. Nach einem Absturz wird ein begonnener Versand nicht automatisch wiederholt, um Doppelversand zu vermeiden.

### Turnier-Guide 2.2 und Standortvorlagen

Die abgestimmte [Produktspezifikation](docs/turnier-mail-konfigurator-spec.md) aus dem anderen Entwicklungs-Chat ist mit dem Backoffice abgeglichen:

- In der Navigation unter **Standorte** findest du die wiederverwendbaren Vorlagen **Tennis Center Bad Saarow**, **Neuenhagen** und **Allgemein**. Anschrift, Navigationslink, Belag, Turnierleitung, Park-/Anreisehinweise, Gastronomie, Umkleiden und Kontakt kannst du selbst pflegen. Es werden bewusst keine alten Preise oder unbestätigten Angaben vorausgefüllt.
- Bei der Erstellung einer neuen Infomail wählst du die Standortvorlage. Ihre Daten werden als **Momentaufnahme** gespeichert, sodass eine spätere Änderung der Stammvorlage keine bereits angelegten E-Mails verändert.
- Beim Bearbeiten des Entwurfs bietet der **Turnier-Guide 2.2** zusätzliche eingeklappte Gruppen für aktuelle Änderungen, Auslosung, Anreise, Spielbetrieb, Nenngeld/Service und Regelungen. Jedes Modul wird nur nach Aktivierung mit ausgefüllten Angaben in die E-Mail aufgenommen.
- Die neue E-Mail-Gestaltung enthält einen deutlich hervorgehobenen Termin-/Ortsbereich und sortierte Informationskarten, auch in der Textalternative. Die bisherige kurze Checkliste bleibt weiterhin nutzbar.
- Offizielle Auslosungs-/Spielplan-URLs und Navigation akzeptieren ausschließlich gültige HTTPS-Links. Die Turnigo- und MyScore-Module prüfen zusätzlich die Domain.

**Noch offen:** eigene Betragstabellen statt mehrzeiliger Texte, nebeneinander angeordnete Digitalservice-Karten sowie getrennte Desktop-/Handy-Vorschau. Die bisherigen technischen Versand- und Freigabesicherungen bleiben bestehen. Alle neuen Bereiche werden vor dem Upload im Backoffice-CI getestet.

### Vorlagen und Versandprotokoll

Standardvorlagen sind im Backoffice sichtbar. Sie werden bei Erstellung einer neuen Infomail übernommen und können dann vollständig bearbeitet werden. Freigegebene Entwürfe und ihre Empfängerlisten werden nicht nachträglich geändert. Das Protokoll zeigt getrennt wartende, angenommene, fehlgeschlagene und übersprungene E-Mails.

## Bestehende Turnier-Mail-Verwaltung

Die bisherige separate Seite https://tennis-schiri.de/turnier-mails.php bleibt bestehen. Ihr möglicherweise vorhandener Datenbestand wird weder gelöscht noch ungefragt übertragen. Ein Link ist im Dashboard vorhanden. Neue Vorgänge können im neuen Backoffice angelegt werden.

## Technik und Schutz

- Neue Dateien: public/admin/index.php und public/admin/lib.php.
- Privater Speicher: vorhandene Newsletter-SQLite-Datenbank außerhalb des Webroots; separate Tabellen bo_contacts, bo_campaigns und bo_deliveries.
- Session-Login wie beim bisherigen Newsletter, Schutz durch HTTPS, HttpOnly, SameSite=Strict und CSRF-Tokens.
- Teilnehmerdaten werden bei der Ausgabe escaped, Rücksprungseiten sind noindex, direkte Aufrufe der Funktionsdatei werden gesperrt.
- Keine Trackingpixel, keine versteckten oder geplanten Massen-Aussendungen.
- Beim Versand: Speicherung des Versuchstatus vor dem Mail-Aufruf, Teilnehmerstatus wird unmittelbar vorher erneut geprüft.
- Automatische GitHub-Prüfung in .github/workflows/backoffice-check.yml; Live-Workflow prüft PHP-Syntax und tests/backoffice-test.php vor dem Upload.
- Datenschutztext nennt die gesonderte Verarbeitung turnierbezogener Teilnehmerkontakte.

## Bewusst für spätere Ausbaustufen vorgesehen

Anhänge/PDF-Versand, mehrere Admin-Benutzer, zentrale dauerhafte Bearbeitung von Standardvorlagen, zeitgesteuerte Erinnerungen und direkte Teilnehmerimporte aus nuLiga per API sind in Version 1 **nicht** enthalten. Aktuell arbeiten wir mit CSV-Import und manuell ausgelöstem Versand. Dies hält die erste Version übersichtlich und sicher testbar.
