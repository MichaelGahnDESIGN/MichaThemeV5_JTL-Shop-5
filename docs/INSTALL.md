# Installation (Entwicklungsstand)

> Pre-Alpha. Das Template wurde statisch geprüft (XML, CSS) und noch nicht in einem laufenden JTL-Shop 5 getestet. Smarty-Blocknamen (`layout-header-head-meta`, `layout-header-header`, `layout-footer`) und Einstellungstypen müssen im Shop verifiziert werden.

1. Ordner `templates/MichaThemeV5` nach `<shop>/templates/` kopieren. Voraussetzung: das Template NOVA ist vorhanden (MichaThemeV5 erbt davon).
2. Im Admin-Bereich unter Darstellung, Template, MichaThemeV5 aktivieren.
3. Einstellungen sind nach Bereichen gegliedert. Mit "(Experte)" markierte Optionen sind für Fortgeschrittene.
4. Shop-Cache leeren (Wartung, Cache).

`template.xml`, `snippets/mt-config.tpl` und `mt/*` (außer `mt-jtl.css`) werden aus dem Optionskatalog des Repos MichaThemeV5-Pro erzeugt. Nicht von Hand ändern.

## Erweiterungen

Dieses Repo enthält nur die kostenlosen Funktionen (Free). Pro-Funktionen und Erweiterungen werden separat und privat ausgeliefert, nicht über dieses Repo (https://theme.michael-gahn.de, Konto erforderlich). Ein optionaler Lizenzschlüssel kann in den Theme-Einstellungen unter „Lizenz“ eingetragen werden; ohne Schlüssel läuft das Theme vollständig.
