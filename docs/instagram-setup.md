# Instagram-Feed – Meta Unified Login

**Status:** Frontend und GitHub-Workflow vorbereitet. Zwei Instagram-Konten sind in der Meta-App "Tennis-Schiri Feed" als Tester angenommen und die beiden Tokens liegen in GitHub Actions Secrets. Der erste erfolgreiche Live-Abruf steht noch aus.

## 1. Meta konfigurieren

Die Meta-App verwendet **Instagram API (Unified Login)**, nicht das ältere "Instagram Login". Bei dieser Konfiguration werden Instagram-Profikonten als Business-Assets autorisiert und Systemnutzer-Zugriffstokens (SUAT) bereitgestellt.

Die App ist für die zwei eigenen professionellen Konten eingerichtet:
- `@tennis.schiri`
- `@tennilo.app`

Die für einen reinen Beitragsfeed benötigte Berechtigung ist `instagram_basic`. **Webhooks werden nicht benötigt**: Wir fragen die aktuellen Beiträge täglich ab, statt Ereignisse von Instagram zu empfangen.

### Besonderheit: Kontozuordnung

Für Unified Login erfolgen die Abfragen über `https://graph.facebook.com/v26.0`.

Vor dem Medienabruf fragt unser Skript über `GET /me/assigned_instagram_accounts?fields=id,username` ab, welches Instagram-Konto dem Systemnutzer freigegeben ist. Danach werden die Beiträge mit `GET /{ig-user-id}/media` geladen.

Wenn die Kontoliste leer ist, muss ggf. in Meta die Business-Asset-Freigabe bzw. die Autorisierung über die erzeugte Unified-Login-Anmelde-URL abgeschlossen werden. **Eine bestätigte Tester-Einladung allein garantiert noch nicht die Zuweisung als Business-Asset.** Den Zugang anhand eines Fehlers im Workflow prüfen, ohne Tokens in Logs zu zeigen.

## 2. GitHub Actions Secrets

Repository: `tennis-schiri/tennis-schiri-website`, unter **Settings → Secrets and variables → Actions**:

| Name | Wert |
|---|---|
| `IG_TOKEN_TENNIS_SCHIRI` | Zugriffstoken für `@tennis.schiri` |
| `IG_TOKEN_TENNILO` | Zugriffstoken für `@tennilo.app` |

Die Secrets bleiben bei GitHub und werden nur im serverseitigen Workflow gelesen. **Es wird kein `IG_ENCRYPTION_KEY` benötigt.** Der frühere Plan eines 60-Tage-Refreshs auf `graph.instagram.com` ist für Unified Login falsch und wurde aus dem Skript entfernt.

Die vorhandenen FTPS-Secrets `FTP_HOST`, `FTP_USER`, `FTP_PASSWORD` bleiben unverändert.

## 3. Erster Test

1. **Actions → Instagram-Beiträge automatisch aktualisieren → Run workflow** starten.
2. Bei erfolgreichem Lauf stehen die lokal zwischengespeicherten Bilder unter `public/assets/instagram/` sowie `public/data/instagram.json`. Der Workflow veröffentlicht die Feed-Daten in der Testumgebung über FTPS.
3. Auf `https://neu.tennis-schiri.de/` unter „Aktuelles aus dem Feed“ prüfen, ob pro Konto bis zu drei neue Beiträge erscheinen.
4. Danach erfolgt der Abruf täglich um 05:23 UTC automatisch.

Wenn der Lauf fehlschlägt, die genaue **Fehlermeldung ohne Tokens** kontrollieren. Besonders relevant ist, ob `/me/assigned_instagram_accounts` die gewünschten Konten zurückgibt.

## Datenschutz und Sicherheit

- Ausschließlich eigene, autorisierte professionelle Instagram-Konten; kein Scraping.
- Die Zugangstokens verlassen GitHub Actions nicht und werden nicht in Git oder auf dem Webspace gespeichert.
- Website-Besucher laden Bilder und Texte vom eigenen Webspace, nicht über Instagram-Widgets.
- Erst beim Klick auf einen Beitrag oder das Profil erfolgt die Weiterleitung zu Instagram.
- Keine Stories und keine privaten Inhalte.
- Bei API-Ausfällen bleiben bereits veröffentlichte Feed-Vorschauen bestehen, bis ein neuer erfolgreicher Lauf stattfindet.
- Die Systemnutzer-Tokens laufen bei der ausgewählten Konfiguration laut Meta nicht regulär ab; bei Widerruf müssen sie neu erzeugt werden.

## Technische Dateien

- `scripts/sync_instagram.py`: Unified-Login-Graph-API, Konto-ID-Auflösung, Bildcache und JSON-Ausgabe
- `.github/workflows/instagram-sync.yml`: täglicher Job und FTPS-Veröffentlichung
- `tests/test_instagram_sync.py`: API- und Eingabeprüfungen ohne reale Tokens
- `public/assets/instagram.js`: Frontend mit sicherer URL-Prüfung
- `public/data/instagram.json`: öffentliches Snapshotformat ohne Secrets
- `public/assets/site.css`: Feed-Design

**Wichtig:** Der Workflowschritt bestätigt erst bei einem erfolgreichen Abruf, ob die von Meta ausgegebenen Tokens bereits für Unified Login und die zugehörigen Business-Assets nutzbar sind.
