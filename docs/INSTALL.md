# Installation (Entwicklungsstand)

> Pre-Alpha. Das Template wurde statisch geprüft (XML, CSS) und noch nicht in einem laufenden JTL-Shop 5 getestet. Smarty-Blocknamen (`layout-header-head-meta`, `layout-header-header`, `layout-footer`) und Einstellungstypen müssen im Shop verifiziert werden.

1. Ordner `templates/MichaThemeV5` nach `<shop>/templates/` kopieren. Voraussetzung: das Template NOVA ist vorhanden (MichaThemeV5 erbt davon).
2. Im Admin-Bereich unter Darstellung, Template, MichaThemeV5 aktivieren.
3. Einstellungen sind nach Bereichen gegliedert. Mit "(Experte)" markierte Optionen sind für Fortgeschrittene.
4. Shop-Cache leeren (Wartung, Cache).

`template.xml`, `snippets/mt-config.tpl` und `mt/*` (außer `mt-jtl.css`) werden aus dem Optionskatalog des Repos MichaThemeV5-Pro erzeugt. Nicht von Hand ändern.

## Erweiterungen

Dieses Repo enthält nur die kostenlosen Funktionen (Free). Pro-Funktionen und Erweiterungen werden separat und privat ausgeliefert, nicht über dieses Repo (https://theme.michael-gahn.de, Konto erforderlich). Ein optionaler Lizenzschlüssel kann in den Theme-Einstellungen unter „Lizenz“ eingetragen werden; ohne Schlüssel läuft das Theme vollständig.

## Recht: Felder und Hinweise

Artikelattribute (JTL-Wawi, Artikel, Attribute; Funktionsattribute gleichen Namens gehen auch): `mt_gpsr_manufacturer`, `mt_gpsr_responsible_person`, `mt_gpsr_contact`, `mt_gpsr_safety_info` und `mt_niedrigster_preis_30` (Text, z. B. 19,90 €; erscheint nur bei Streichpreis). Steuer- und Versandhinweis, Lieferzeit, Grundpreis, die Widerrufsfunktion (ab 5.7) und das EU-Label (ab 5.8) kommen von JTL bzw. NOVA und werden nicht doppelt ausgegeben. Für Videos mit 2-Klick den Baustein `snippets/mt-video-consent.tpl` einbinden. Block-Namen stammen aus NOVA 5.8.2 und sind im Shop zu prüfen. Technische Hinweise, keine Rechtsberatung. Texte und Einsatz anwaltlich prüfen lassen. Eigene Cookie-Banner liefert das Theme nicht; den Bestellbutton beschriftet die Kasse bzw. ein Rechtsplugin (Button-Lösung § 312j BGB).