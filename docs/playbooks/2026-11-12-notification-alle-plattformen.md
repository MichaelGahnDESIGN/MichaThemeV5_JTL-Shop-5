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
| WP-1 | Menü-Platzierung mit Divi 5 + Rückfall auf schwebend | `includes/class-mgd-notification-renderer.php` | Glocke erscheint auch ohne `theme_location` und ohne Menü | PHP-Test mit WP-Stubs | geprüft | `tests/renderer-placement.php` 15/0; echtes WP: Rückfall schwebend; Skript-Fix `wp_footer` Prio 5 |
| WP-2 | Backend: Anzahl, Icon-Auswahl, Karten-Look, Akzent blau | `includes/class-mgd-notification-admin.php`, `assets/admin.css` | Einstellungen werden gespeichert und genutzt | echtes WP im Browser | geprüft | gespeichert: menu/3/megaphone, Hinweis sichtbar |
| WP-3 | REST: Alle gelesen, Ausblenden, Gelesene ausblenden | `includes/class-mgd-notification-rest.php`, `class-mgd-notification-repository.php` | nur angemeldet, nur eigene sichtbare Einträge | echtes WP + DB | geprüft | ohne Login 401; geplante Meldung bleibt ungelesen |
| WP-4 | Frontend: Anzahl, „Alle anzeigen“, Links, Ausblenden, SVG-Icon | `assets/frontend.js`, `assets/frontend.css`, `templates/notification-center.php` | Sichtprüfung breit/schmal, Links sicher | Browser | geprüft | 375 px ohne Querscrollen, Skript-Inhalt wird verworfen |
| WP-5 | Release 0.3.0 | Version, README, `scripts/build-package.py` | Release mit ZIP (755/644), Download gleich | `gh release download` + `cmp` | geprüft | PR #17, Release v0.3.0, Download gleich |
| JTL-1 | Plugin 0.3.0: Einstellungen, IO gelesen/ausblenden | Plugin `MichaThemeV5NotificationCenter` | Einstellung wirkt, IO nur mit Token | Tests + Demo-Shop | geprüft | Tests 20/0 + 34/0; Demo: Update Code 1, Token-Prüfung ok |
| JTL-2 | Theme: „Alle anzeigen“, Alle gelesen, Ausblenden | `templates/MichaThemeV5` (nur Glocke) | Sichtprüfung | Browser mit Theme-Tokens | geprüft | PR #22, Release mgd-notification-center-v0.3.0; echtes Shop-Frontend nicht geprüft (Wartungsmodus) |
| SW-1 | Shopware: gleiche Funktionen | `MichaThemeV5_Shopware-6/src/NotificationCenter`, Storefront | | | geplant | |
| SH-1 | Shopify: gleiche Funktionen | `michatheme-v5-app/extensions/michatheme-notification` | | | geplant | |

## Planänderungen

- 12.11.2026: Michael wünscht zusätzlich: Ausblenden-Knopf, „Gelesene ausblenden“, eingebettete Icons mit Auswahl, schöneres Backend, Akzent blau (WordPress). Übernommen in WP-2 bis WP-4 und JTL.
- 12.11.2026: Update-Client JTL neu erzeugt (Generator Pro 0.40.11).
