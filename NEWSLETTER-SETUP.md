# Tennis-Schiri-Newsletter – Betrieb und Einrichtung

Die Newsletter-Funktion liegt im **separaten** Tennis-Schiri-Webspace. Sie nutzt PHP, SQLite und den Mailtransport des eigenen Hostings. Für den Versand ist kein externer Newsletterdienst erforderlich.

## Dateien im öffentlichen Verzeichnis

- `/newsletter/`: Anmelde-Seite mit Auswahl von Turnieren, Regelwissen und Workshops.
- `/newsletter.php`: Double-Opt-in, Bestätigung und Abmeldung.
- `/newsletter-admin.php`: geschützte Abonnentenverwaltung und CSV-Export.
- `/newsletter-mail.php`: Entwürfe, Testmails, Freigabe, Versandstatus und manuelle Versandschritte.
- `/newsletter-setup.php`: **einmaliger** per E-Mail geschützter Einrichtungsassistent.

Die Formulare sind zusätzlich auf der Startseite eingebunden; von der Turnierseite wird zur Newsletter-Anmeldung verlinkt. Bei nicht eingerichteter Konfiguration bleibt die Annahme von Anmeldungen deaktiviert.

## Einmalige Einrichtung

1. Den [Live-Workflow](https://github.com/tennis-schiri/tennis-schiri-website/actions/workflows/deploy-live.yml) manuell starten (**Run workflow**).
2. Nach erfolgreichem Upload **https://tennis-schiri.de/newsletter-setup.php** aufrufen.
3. Die Einrichtung über einen Link bestätigen, der an **mw@tennis-schiri.de** verschickt wird. Der Link gilt 30 Minuten.
4. Ein eigenes Admin-Passwort von mindestens 16 Zeichen vergeben und die Einrichtung abschließen.
5. **https://tennis-schiri.de/newsletter-admin.php** öffnen und anmelden.
6. Die Anmeldung und den Versand mit einer eigenen E-Mail-Adresse testen: Anmelden, Bestätigungslink anklicken, neue Anmeldung in der Verwaltung prüfen, Testmail erstellen.

**Es werden keine Passwörter oder privaten Schlüssel in GitHub eingetragen.** Wenn PHP den privaten Ordner nicht anlegen darf, legt man beim Hoster den Ordner `tennis-schiri-newsletter-data` direkt **neben** `tennis-schiri-live` an. Er muss für PHP beschreibbar sein. Niemals private Datenbanken oder Konfigurationsdateien in das öffentliche Verzeichnis legen.

## Interne Datenspeicherung

Der Einrichtungsassistent erstellt **außerhalb des Webroots** die Dateien:

- `tennis-schiri-newsletter-config.php` (Zugangsschutz, private Verschlüsselungs-/Signaturschlüssel)
- `tennis-schiri-newsletter-data/tennis-schiri-newsletter.sqlite` (Empfänger, Einwilligungen, Versandstatus)

Dieser Speicher ist unabhängig von TENNILO Founders. Bestehende TENNILO-Interessenten werden nicht importiert.

## Nachrichten versenden

Unter `newsletter-mail.php` einen Entwurf mit Betreff, Text und genau einem Thema anlegen. Erst eine **Testmail** prüfen. Durch Eingabe von **SENDEN** wird die Empfängerliste der bestätigten Kontakte zum gewählten Thema eingefroren. Das ist **noch kein Versand**. Danach wird der Versand manuell in Paketen mit bis zu fünf E-Mails pro Klick ausgelöst. Vor jedem Versandversuch werden Abmeldestatus und Themeninteressen erneut geprüft.

E-Mails enthalten einen signierten Abmeldelink. Es werden keine Öffnungs- oder Klicktracker eingesetzt. Ein vom Mailserver akzeptierter Versand ist noch keine bestätigte Zustellung. Bei unklarer Zustellung werden Empfänger aus Gründen der Doppelversandvermeidung nicht blind erneut verschickt.

## Betriebshinweise

- Die Funktion erfordert PHP 8.1+, PDO_SQLITE, HTTPS und die PHP-Mailfunktion. Das Absenderpostfach **mw@tennis-schiri.de** sollte funktionieren.
- Eine tatsächliche E-Mail-Zustellung erfordert passende Domain-Mailkonfiguration (insbesondere SPF und idealerweise DKIM/DMARC) und muss mit echten Testmails überprüft werden.
- Bei steigenden Empfängerzahlen sind Versandlimits des Hosters zu beachten; es erfolgt ausdrücklich kein unbeaufsichtigter Massenversand.
- Für Backups die private SQLite-Datei geschützt sichern. CSV-Exporte enthalten personenbezogene Daten, daher nicht öffentlich ablegen.
- Bei Einwilligungsänderungen, automatischem E-Mail-Versand oder zusätzlichen Auswertungen sind technische und datenschutzrechtliche Abläufe erneut zu prüfen.
- `newsletter-setup.php` ist nach abgeschlossener Einrichtung automatisch gesperrt.
