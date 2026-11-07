# Tennis-Schiri Turnier-Mails

Eigener Verwaltungsbereich unter `/turnier-mails.php`. Die Anmeldung verwendet die bestehende geschützte Newsletter-Admin-Sitzung, **nicht** deren Empfängerliste.

## Veröffentlichung

[Live-Deployment](https://github.com/tennis-schiri/tennis-schiri-website/actions/workflows/deploy-live.yml) über **Run workflow** manuell starten. Das Deployment führt `php -l public/turnier-mails.php` aus und kopiert die Datei in den Webspace. Anschließend `https://tennis-schiri.de/newsletter-admin.php` öffnen und auf **Turnier-Mails verwalten** klicken.

## Speicher

Eine separate SQLite-Datei `tennis-schiri-turnier-mails.sqlite` liegt im bestehenden privaten Newsletter-Datenordner **außerhalb** des Webroots. Die PHP-Prozessberechtigung zum Schreiben dort wird benötigt. Weder Teilnehmerdaten noch Postfachkennwörter stehen in GitHub.

## Absender und Transport

Absender, Envelope-Sender und Reply-To: `turniere@tennis-schiri.de`. Der Versand nutzt wie das bestehende Newsletter-System PHP `mail()`. Dass das Postfach existiert, beweist noch nicht die Zustellbarkeit; mit einem Test an `mw@tennis-schiri.de` und einer externen Adresse prüfen. SPF, DKIM, DMARC sowie Versandlimits beim Hoster kontrollieren. SMTP-Zugangsdaten sind in dieser Version nicht hinterlegt.

## Ablauf

1. Neues Turnier anlegen; Termin, Ort und Turnierseiten-Link eintragen.
2. Mail-Entwurf mit organisatorischen Hinweisen ergänzen und speichern. Platzhalter **vor** dem Versand löschen.
3. Teilnehmer per CSV (mit einzelner E-Mail-Spalte) oder Textfeld importieren; pro Turnier werden gleiche Adressen nur einmal gespeichert. Kein Newsletter-Import.
4. HTML-Vorschau öffnen und Testmail senden. Der Test wird als Test markiert.
5. Mit `FREIGEBEN` Inhalt und Empfänger einfrieren. Diese Aktion sendet noch nichts.
6. Mit `SENDEN` jeweils bis zu fünf noch offene Empfänger verschicken. Jede Adresse wird vor dem Versandversuch als versucht markiert. Fehlgeschlagene oder unklare Versuche werden bewusst **nicht automatisch wiederholt**.

Die Nachricht enthält eine HTML- und eine Textversion, übernimmt Logo/Farben des Tennis-Schiri-Newsletters, enthält aber **keine Newsletter-Abmeldung**, da es sich ausschließlich um organisatorische Informationen zu einem gebuchten Turnier handeln soll. Vor jedem Versand sicherstellen, dass Empfänger und Inhalt tatsächlich der konkreten Turnierteilnahme dienen.

**Statusbegriffe:** `pending` = noch nicht versucht, `accepted` = PHP-Mailtransport hat den Versandversuch akzeptiert (keine Zustellgarantie), `failed` = Versandfunktion meldete Fehler. Für erneute Aussendungen oder Korrekturen neues Turnier bzw. eigenständige zukünftige Kampagnenfunktion verwenden.

**Noch nicht implementiert:** SMTP-Anbindung, automatischer Import aus nuLiga, automatische Termine aus Turnierdaten, einzelne Versandkorrekturen sowie Live-Zustellungsbestätigungen.
