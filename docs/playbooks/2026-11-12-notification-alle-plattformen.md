# Playbook: Notification Center – „Alle anzeigen“, Anzahl, Alle gelesen, Ausblenden

Stand: 12.11.2026 · Auftrag von Michael in diesem Thread (`/fast-dev`)

## Ziel

1. WordPress-Plugin funktioniert im Frontend und im Backend richtig (auch mit Divi 5).
2. Für alle Versionen: Im Backend einstellbar, wie viele aktuelle Meldungen im Frontend stehen.
3. Unten im Panel „Alle anzeigen“: Modal mit allen Meldungen, Überschrift sichtbar, Inhalt ausklappbar, Links klickbar.
4. „Alle gelesen“ markiert wirklich alle. Nutzer können Meldungen im Frontend ausblenden.

## Grenzen

- Nur Dateien des Notification Centers anfassen. campingteile24.de nie.
- Push, Release und Server-Upload sind freigegeben („mach einen neuen Release“). Löschen auf Servern nicht.
- Prüfsummen-JSON nur über das Pro-Repo bzw. den Plattform-Thread, nie direkt auf den Server.
- Zugangsdaten (`MICHA/`, `MzIaCuHbAeTrHwEoMrEt.md`, `.env`) nur lesen.

## Bestand (geprüft 12.11.2026)

| System | Wo | Aktualisierbar? |
|---|---|---|
| WordPress | Repo `MGD-Notification-WordPress`, Plugin 0.2.0 | Ja, über den MGD-Plugin-Katalog im Plugin (GitHub-Releases, `Update URI`) |
| JTL-Shop 5 | dieses Repo, Plugin 0.2.1 + Glocke im Theme | Ja, Update Client mit Prüfsummen-Datei |
| Shopware 6 | im Theme-Plugin `MichaThemeV5_Shopware-6` | Nur mit dem Theme-Plugin zusammen |
| Shopify | App-Erweiterung in `MichaThemeV5_Shopify/michatheme-v5-app` | Über `shopify app deploy`, kein eigener Update-Weg |

## Schritte

| ID | Schritt | Dateien | Erfolgskriterium | Prüfung | Status | Beleg |
|---|---|---|---|---|---|---|
| WP-1 | Menü-Platzierung mit Divi 5 + Rückfall auf schwebend | `includes/class-mgd-notification-renderer.php` | Glocke erscheint auch ohne `theme_location` und ohne Menü | PHP-Test mit WP-Stubs | geplant | |
| WP-2 | Backend: Anzahl einstellbar, alle Einstellungen speichern | `includes/class-mgd-notification-admin.php`, `class-mgd-notification-plugin.php` | Feld „Anzahl im Panel“ 1–30 wird gespeichert und genutzt | PHP-Test | geplant | |
| WP-3 | REST: Alle gelesen in einem Aufruf, Ausblenden | `includes/class-mgd-notification-rest.php`, `class-mgd-notification-repository.php` | Routen nur für angemeldete Nutzer, nur eigene Einträge | PHP-Test | geplant | |
| WP-4 | Frontend: Anzahl, „Alle anzeigen“-Modal, ausklappbar mit Links, Ausblenden | `assets/frontend.js`, `assets/frontend.css`, `templates/notification-center.php` | Sichtprüfung breit/schmal, Links sicher | Browser-Test mit Testseite | geplant | |
| WP-5 | Release 0.3.0 | Version, CHANGELOG, `scripts/build-package.py` | Release mit ZIP (Rechte 755/644), Download gleich | `gh release download` + `cmp` | geplant | |
| JTL-1 | Plugin 0.3.0: Anzahl-Einstellung, Ausblenden/Gelesen | Plugin `MichaThemeV5NotificationCenter` | Einstellung wirkt, Theme bekommt Daten | Tests | geplant | |
| JTL-2 | Theme: „Alle anzeigen“-Modal, Alle gelesen, Ausblenden | `templates/MichaThemeV5` (nur Glocke) | Sichtprüfung | Browser | geplant | |
| SW-1 | Shopware: gleiche Funktionen | `MichaThemeV5_Shopware-6/src/NotificationCenter`, Storefront | | | geplant | |
| SH-1 | Shopify: gleiche Funktionen | `michatheme-v5-app/extensions/michatheme-notification` | | | geplant | |

## Planänderungen

(noch keine)
