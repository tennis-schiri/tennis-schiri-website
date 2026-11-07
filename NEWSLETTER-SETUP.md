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

Unter `newsletter-mail.php` einen Entwurf mit Betreff, Haupttext und genau einem Thema anlegen. Optional lassen sich eine andere Überschrift, ein Vorschautext für den Posteingang, eine hervorgehobene **Turnier-/Veranstaltungskarte mit Termin und Ort** sowie ein Aktionsbutton mit Beschriftung und HTTPS-Link ergänzen. Den Entwurf speichern und über **HTML-Vorschau öffnen** kontrollieren. Danach eine **Testmail an mw@tennis-schiri.de** schicken und in verschiedenen E-Mail-Programmen ansehen. Erst nach dieser Kontrolle durch Eingabe von **SENDEN** die Empfängerliste der bestätigten Kontakte zum gewählten Thema freigeben. Das ist **noch kein Versand**. Danach wird der Versand manuell in Paketen mit bis zu fünf E-Mails pro Klick ausgelöst. Vor jedem Versandversuch werden Abmeldestatus und Themeninteressen erneut geprüft.

Das Newsletter-Layout wird serverseitig aus `public/newsletter-template.php` erzeugt. Es nutzt die Tennis-Schiri-Farben (dunkles Grün, helles Grün), ein tabellenbasiertes Design für E-Mail-Clients und einen festen Footer. Es bindet **keine Skripte oder Tracking-Pixel** ein. Wenn du Partner oder TENNILO im Newsletter auswählst, werden deren Original-Logos als öffentlich gehostete Bilder eingebunden (Partnerlogos unter tennis-schiri.de, TENNILO-Logo unter tennilo.de). Manche Mailprogramme blockieren externe Bilder zunächst; Namen und Links bleiben auch dann sichtbar. Redaktioneller Text wird vor HTML-Ausgabe escaped; zusätzliche HTML-Codes müssen nicht eingegeben werden. Jede E-Mail wird als **multipart/alternative** verschickt: gestaltete HTML-Version und reine Textfassung. Bestehende Kampagnen werden nicht gelöscht; fehlende zusätzliche Spalten ergänzt die Verwaltung beim nächsten Aufruf der Kampagnenseite automatisch. **Abgeschlossene und freigegebene Kampagnen bleiben unveränderbar.**

Die Vorlagenfunktion wird in GitHub vor dem Live-Upload durch `php tests/newsletter-template-test.php` geprüft. Die Testmail verwendet den allgemeinen Abmeldungsaufruf; **echte Rundmails** erhalten ausschließlich individuelle, signierte Abmeldelinks. **Testmail und Browser-Vorschau** tragen einen sichtbaren Testhinweis. Eine freigegebene Rundmail enthält keinen Testhinweis.

E-Mails enthalten einen signierten Abmeldelink. Es werden keine Öffnungs- oder Klicktracker eingesetzt. Ein vom Mailserver akzeptierter Versand ist noch keine bestätigte Zustellung. Bei unklarer Zustellung werden Empfänger aus Gründen der Doppelversandvermeidung nicht blind erneut verschickt.

## Turniere und Veranstaltungen automatisch übernehmen

Die Newsletter-Verwaltung lädt zukünftige Turniere und datierte Workshops direkt aus dem **veröffentlichten Website-Katalog** `/data/newsletter-events.json`.

- Quelle sind die aktuellen Turnierkarten von `public/turniere/index.html` und datierte Workshops von `public/leistungen/index.html`.
- Beim **manuellen GitHub-Live-Upload** wird die Liste automatisch durch `scripts/build_newsletter_events.py` neu erzeugt. Du pflegst Termine, Orte und Links weiterhin nur auf der Website.
- Im Newsletter-Entwurf findest du unter **„Turniere & Veranstaltungen übernehmen“** die Termine mit Kontrollkästchen und einem Link zur Quelle. Du kannst beliebig mehrere (maximal 25) auswählen.
- Ein Klick auf **„Entwurf speichern“** speichert die ausgewählten Inhalte als Momentaufnahme. Die HTML-Vorlage ergänzt die passenden Karten mit Datum, Ort und dem direkten Link. Die reine Textfassung erhält dieselben Links.
- Wo tennis.de noch keinen Anmeldelink veröffentlicht hat, wird die entsprechende Turnierdetailseite auf tennis-schiri.de verlinkt. Für Workshops gibt es einen Link direkt zur konkreten Workshop-Sektion.
- Ändern sich Website-Termine später, wird die **Auswahl neuer Entwürfe** beim nächsten Live-Upload automatisch aktualisiert. Bestehende, insbesondere bereits freigegebene Rundmails ändern sich nicht heimlich. Entwürfe können bewusst erneut gespeichert werden.
- Vergangene Termine verschwinden aus der Liste neuer Entwürfe; bereits gespeicherte Auswahlen bleiben in vorhandenen Entwürfen sichtbar, damit du sie überprüfen und entfernen kannst.
- Der Empfängerkreis richtet sich weiterhin nach dem gewählten Interessengebiet (Turniere, Regeln oder Workshops); das Ankreuzen von Terminen verändert **nicht** die Newsletter-Einwilligungen.

**Wichtig:** Die Liste wird nicht live von tennis.de ausgelesen. Sie spiegelt die Termine und Links auf unserer eigenen Website wider. Ein Newsletter wird niemals allein durch einen neuen Termin automatisch verschickt.

## Partner und TENNILO als kleine Banner

Unter **„Partner & TENNILO einblenden“** kannst du unabhängig von der Terminauswahl einzelne Partner oder TENNILO anhaken. Nur die gewählten erscheinen im fertigen Newsletter.

- **Alle sieben Partner** kommen mit ihren Original-Logos, Namen und offiziellen Zieladressen aus der öffentlichen Partnerseite (`public/partner/index.html`). Es wird keine zweite Liste manuell gepflegt.
- **TENNILO** ist zusätzlich als eigenes Projekt auswählbar, mit dem Original-Logo von `https://tennilo.de/assets/tennilo-logo.png` und einem Link auf `https://tennilo.de/`.
- Der automatische Katalog `/data/newsletter-partners.json` wird bei jedem GitHub-Live-Upload durch `scripts/build_newsletter_partners.py` neu erstellt. Die Einträge liegen öffentlich und enthalten keine Abonnentendaten.
- Das Checkbox-Modul befindet sich in `public/newsletter-partners.php`. Beim **Speichern des Entwurfs** wird die Auswahl in der privaten SQLite-Datenbank eingefroren. Bereits freigegebene Newsletter verändern sich nicht nachträglich.
- Die HTML-Vorlage erstellt kleine horizontale Logo-Banner. Bild und Partnername verlinken auf die jeweilige Website; die Textalternative enthält ebenfalls alle ausgewählten Partnerlinks.
- Kein Partner und auch TENNILO werden automatisch in jede Ausgabe eingebaut. Die Auswahl ist freiwillig und beeinflusst nicht die Abonnentengruppen oder Versandfreigabe.
- Vor dem Versand die Browser-Vorschau und eine Testmail prüfen. Im E-Mail-Programm mit deaktivierten Bildern bleiben die Partnernamen und Links erhalten.

**Datenschutz:** Beim Anzeigen der extern geladenen Logos entsteht ein technischer Bildabruf. Die Partnerlogos werden von tennis-schiri.de bereitgestellt, das TENNILO-Logo von tennilo.de; es sind keine Trackingpixel eingebaut.

## Einheitliches Tennis-Schiri-Logo

Das finale freigegebene Logo **TS-09c** liegt als unverändertes Original-Set im Repository unter `branding/tennis-schiri-ts09c-logo-set.zip` (Quelle: Google Drive, Ordner „Logo Tennis-Schiri – TS-09c“).

Beim Live-Upload entpackt `scripts/build_site_branding.py` die Original-PNG-Dateien in `public/assets/branding/` und kopiert sie auf den Webspace. Alle Website-Seiten verwenden die dunkle Wortmarke im Header und Footer; das neue TS-Favicon ersetzt die alte CSS-Variante. Social-Previews verlinken die Original-Logo-Variante mit Tennisball. Der Newsletter-Kopf zeigt ebenfalls das Original-Logo. Die Verwaltungs- und Bestätigungsseiten tragen dieselbe Marke.

Für E-Mail-Clients wird die Headergrafik unter `https://tennis-schiri.de/assets/branding/ts-logo-dunkel.png` abgerufen. Damit bleibt die E-Mail kompakt; falls Bilder im Postfach blockiert sind, wird der Alternativtext „Tennis-Schiri“ angezeigt. Trackingpixel sind nicht enthalten.

Die Prüfung `python tests/site-branding-test.py` ist in den Live-Upload integriert. Zusätzlich kontrolliert die GitHub-Action `branding-check.yml` die Originalbilder und alle 13 Website-Seiten.

## Betriebshinweise

- Die Funktion erfordert PHP 8.1+, PDO_SQLITE, HTTPS und die PHP-Mailfunktion. Alle Newsletter-Nachrichten werden mit **news@tennis-schiri.de** als Absender und Reply-To verschickt. Das Absenderpostfach sollte aktiv sein, SPF/DKIM/DMARC sind zu prüfen.
- Die Einrichtungsbestätigung, Testmails und interne Benachrichtigungen werden an **mw@tennis-schiri.de** zugestellt, aber von **news@tennis-schiri.de** versendet.
- Eine tatsächliche E-Mail-Zustellung erfordert passende Domain-Mailkonfiguration (insbesondere SPF und idealerweise DKIM/DMARC) und muss mit echten Testmails überprüft werden.
- Bei steigenden Empfängerzahlen sind Versandlimits des Hosters zu beachten; es erfolgt ausdrücklich kein unbeaufsichtigter Massenversand.
- Für Backups die private SQLite-Datei geschützt sichern. CSV-Exporte enthalten personenbezogene Daten, daher nicht öffentlich ablegen.
- Bei Einwilligungsänderungen, automatischem E-Mail-Versand oder zusätzlichen Auswertungen sind technische und datenschutzrechtliche Abläufe erneut zu prüfen.
- `newsletter-setup.php` ist nach abgeschlossener Einrichtung automatisch gesperrt.
