# Eigener Instagram-Feed – Ersteinrichtung

**Status:** Die Website-Anzeige und der GitHub-Workflow sind vorbereitet. Ohne persönliche Meta-Autorisierung erscheinen zunächst direkte Links auf die Profile statt Beitragsbildern. Keine Instagram-Zugangsdaten im Browser, HTML oder öffentlichen JSON ablegen.

## 1. Meta/Instagram vorbereiten

Für beide Profile werden professionelle Instagram-Konten (Business oder Creator) benötigt:
- Tennis-Schiri: `@tennis.schiri`
- TENNILO: `@tennilo.app`

Unter [Meta for Developers](https://developers.facebook.com/apps/) eine App mit **Instagram API with Instagram Login** einrichten und für beide eigenen Konten den API-Zugriff autorisieren. Die minimale Berechtigung für den Abruf ist `instagram_business_basic`; es werden **keine** Berechtigungen zum Posten, Lesen von Nachrichten oder Verwalten von Kommentaren gebraucht.

In Meta → Instagram → API setup with Instagram Login → Generate access tokens jeweils einen **Long-Lived Access Token (ca. 60 Tage)** erstellen. Bei der ersten Autorisierung sollten beide Konten als Instagram-Tester/Accounts in der App eingerichtet sein. Eine Facebook-Seite ist für diese Login-Variante nicht erforderlich.

> Die Tokens sind wie Passwörter zu behandeln. Nicht im Chat, auf einer Website, in Commit-Messages oder in der GitHub-README veröffentlichen.

## 2. GitHub-Secrets hinterlegen

Im Repository `tennis-schiri/tennis-schiri-website` unter **Settings → Secrets and variables → Actions → New repository secret** diese drei Secrets erstellen:

| Name | Wert |
|---|---|
| `IG_TOKEN_TENNIS_SCHIRI` | Long-Lived-Token von `@tennis.schiri` |
| `IG_TOKEN_TENNILO` | Long-Lived-Token von `@tennilo.app` |
| `IG_ENCRYPTION_KEY` | Eigener, zufälliger Fernet-Schlüssel (siehe Befehl) |

Ein gültiger Schlüssel lässt sich mit Python lokal erzeugen (auch Windows: `py` statt `python`):

```sh
python -c "import base64,secrets; print(base64.urlsafe_b64encode(secrets.token_bytes(32)).decode())"
```

Den ausgegebenen Schlüssel als **GitHub-Secret** hinterlegen. Nicht in die Repository-Dateien schreiben und nicht an andere senden.

Die vorhandenen FTPS-Secrets `FTP_HOST`, `FTP_USER`, `FTP_PASSWORD` bleiben unverändert. Der FTPS-Benutzer muss weiterhin nur auf das Testverzeichnis zugreifen können.

## 3. Einmaliger Start

1. Zuerst die normale Website einmal über **Actions → Website per FTPS veröffentlichen → Run workflow** veröffentlichen. Damit werden `assets/instagram.js`, `data/instagram.json` und die neuen Styles auf `neu.tennis-schiri.de` bereitgestellt.
2. Danach **Actions → Instagram-Beiträge automatisch aktualisieren → Run workflow** ausführen. Einmalig werden die Vorschaudaten geladen, Bilder lokal in `public/assets/instagram/` gespeichert und per FTPS auf den Testserver hochgeladen.
3. In der Startseite unter „Aktuelles aus dem Feed“ prüfen, ob aus jedem Konto bis zu drei Beiträge erscheinen. Fotos, Reels und Karussells erhalten – sofern von Meta geliefert – ein Vorschaubild.
4. Danach läuft der Abgleich **täglich um 05:23 UTC** automatisch. Ein manuelles Ausrollen der gesamten Website ist für neue Posts nicht erforderlich.

## 4. Automatische Token-Verlängerung

Langzeit-Tokens müssen bei Meta spätestens vor Ablauf verlängert werden. Das GitHub-Skript versucht die Erneuerung nach der Initialisierung und danach etwa alle 25 Tage. Meta erlaubt die Verlängerung erst, wenn der bisherige Token mindestens 24 Stunden alt und noch gültig ist. Bei einem brandneuen Token kann der erste Versuch scheitern; der nächste tägliche Lauf versucht es erneut.

Die aktuellen Tokens werden **Fernet-verschlüsselt** in `private/instagram-tokens.enc` im privaten GitHub-Repository gespeichert. **Nur** das Geheimnis `IG_ENCRYPTION_KEY` kann sie entschlüsseln. Dateien unter `private/` werden **nie** auf den Webspace geladen. Nach erfolgreicher Ersteinrichtung werden die Tokens der GitHub-Secrets nur noch als Notfall-/Wiederherstellungswert genutzt.

Wird ein Instagram-Zugang widerrufen oder ein Token unwiderruflich ungültig, in Meta neue Tokens ausstellen, beide `IG_TOKEN_…`-Secrets aktualisieren und den Instagram-Workflow mit der Option **reauthorize = true** starten. Den Verschlüsselungsschlüssel dabei unverändert lassen (außer bei gezielter vollständiger Rotation).

## Datenschutz und Sicherheit

- Nur offiziell autorisierte eigene professionelle Konten; kein Scraping.
- Token und Instagram-App-Zugangsdaten ausschließlich in GitHub-Secrets bzw. als verschlüsselter Stand im privaten Repo.
- Alle für Besucher sichtbaren Bilder und Texte werden serverseitig synchronisiert und lokal ausgeliefert.
- Besucher erhalten keine API-Tokens und laden keine Instagram-Widgets oder Instagram-JavaScript.
- Erst beim Klick auf einen Beitrag oder Profil-Link erfolgt eine Weiterleitung zu Instagram.
- Die Integration synchronisiert **keine Stories** und keine privaten Inhalte.
- Bei API-Ausfällen werden vorhandene öffentliche Vorschauen beibehalten; neue Beiträge erscheinen erst nach erfolgreichem Lauf.

## Technische Dateien

- `scripts/sync_instagram.py`: Meta-API-Aufruf, Bildcache, Tokenrefresh und JSON-Ausgabe
- `scripts/instagram-requirements.txt`: Python-Paket für Verschlüsselung
- `.github/workflows/instagram-sync.yml`: Zeitplan, verschlüsselte Sicherung und FTPS-Upload nur der Feed-Dateien
- `public/assets/instagram.js`: Frontend mit Sicherheitsprüfung auf Instagram-Links und lokale Bilder
- `public/data/instagram.json`: öffentliches Snapshotformat ohne Zugangsdaten
- `public/assets/site.css`: Feed-Design

**Hinweis:** Die Meta-API und GitHub Actions können sich ändern. Bei Zugriffsfehlern Logs des Instagram-Workflows überprüfen; die Tokens dürfen dabei nicht in Logs kopiert werden. Der tägliche Zeitplan kann bei GitHub zeitlich verzögert ausgeführt werden.