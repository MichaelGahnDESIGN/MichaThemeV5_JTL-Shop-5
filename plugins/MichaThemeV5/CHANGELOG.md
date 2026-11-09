# Änderungen am Plugin MichaThemeV5 (JTL-Shop 5)

## Unveröffentlicht
- Neu: Tab „Updates“ (MGD Update Client). Update-Prüfung nur nach Einschalten oder Klick, Paket herunterladen und prüfen (Signatur, SHA-256, Inhalt). Es wird nichts installiert: Das ZIP kommt in die JTL-Pluginverwaltung.
- Der Update-Kanal ist noch nicht eingerichtet (B1 Schlüssel fehlen, B2 Produkt auf dem Server). Bis dahin zeigt der Tab nur einen Hinweis.

## 0.6.1 (2026-11-09)
- Mitgeliefertes Template mit korrigiertem Hell-/Dunkelmodus, lesbaren eingefügten Texten, passenden Suchfarben und bedienbaren Navigationspfeilen.
- Einstellungsplugin und Template-Bündel tragen dieselbe Version. Das vollständige Bündel ist auch im Pluginordner des Gesamtpakets enthalten.
- Gespeicherte Einstellungen bleiben erhalten; Template weiterhin nur nach bewusster Auswahl im Info-Tab kopieren.

## 0.6.0 (2026-11-01)
- Neu: Tab „Demo-Inhalte“ im Plugin-Menü (erscheint nach dem Plugin-Update): 16 Beispielartikel in 4 Kategorien mit 48 Platzhalterbildern importieren und wieder entfernen. Wiederholbar, keine Dubletten.
- Per Konsole: `php plugins/MichaThemeV5/src/Generated/demo-cli.php import|status|remove [--yes]`.
- Entfernen löscht nur markierte Demo-Einträge. Vor der Nutzung bitte ein Backup des Shops machen.
- Ehrlich gesagt: per Konsole auf JTL-Shop 5.8.0 erprobt, die Backend-Seite noch nicht im Admin geprüft.

## 0.5.1 (2026-10-28)
- Neu: Das Plugin MichaThemeV5 mit vier Tabs im Menü „Plugins“: Einstellungen, Lizenzen, Plugin-Empfehlungen, Info.
- Einstellungen: Design-Presets, Suche, Einfach/Experte, 16 Farbfelder je Hell und Dunkel mit Farbwähler, Palette (12 Farben) und Kontrast-Warnung, Layout (Kartenstil, Flächenstil, Inhaltsbreite).
- Die Werte liegen im gleichen Speicher wie die Template-Einstellungen. Es wird nichts umgezogen, nichts geht verloren, auch wenn das Plugin wieder entfernt wird.
- Lizenzen: Lizenzcode einfügen, Status, Link zum Kauf.
- Plugin-Empfehlungen: Liste von theme.michael-gahn.de. Ist die Liste nicht erreichbar, läuft der Shop normal weiter.
- Info: Statusprüfung, Template einrichten (nur per Knopf, mit Rückfrage, ZIP-Sicherung und Prüfsummen), Sicherung der Einstellungen, Changelog.
- Das Template funktioniert weiter ohne das Plugin.
