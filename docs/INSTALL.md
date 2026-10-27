# Installation (Pre-Alpha)

> **Wichtig:** Das Theme ist eine frühe Testversion (Pre-Alpha). Es wurde in einem Demo-Shop mit JTL-Shop 5.8.2 getestet. In anderen Shops und Versionen ist es nicht geprüft. Bitte zuerst in einem Test-Shop ausprobieren, nicht direkt im Live-Shop. Vorher eine Sicherung machen.

## Neu installieren

1. Auf GitHub unter „Releases“ die Datei `michatheme-v5-jtl5-<Version>.zip` herunterladen. Daneben liegt eine Datei mit der Endung `.sha256`. Damit lässt sich prüfen, ob die ZIP-Datei unverändert ist.
2. ZIP-Datei entpacken.
3. Den Ordner `templates/MichaThemeV5` in den Ordner `<Ihr Shop>/templates/` kopieren. Das Template NOVA muss im Shop vorhanden sein (MichaThemeV5 erbt davon).
4. Im Admin-Bereich: Darstellung, Template, MichaThemeV5 aktivieren.
5. Shop-Cache leeren (Wartung, Cache).
6. Einstellungen sind nach Bereichen gegliedert. Mit „(Experte)“ markierte Optionen sind für Fortgeschrittene.

## Von einer älteren Version aktualisieren

1. Sicherung des Ordners `templates/MichaThemeV5` machen.
2. Den Ordner durch den neuen ersetzen.
3. Shop-Cache leeren.
4. Die gespeicherten Einstellungen bleiben erhalten. Neue Einstellungen starten mit ihrem Standardwert.

Glocke (Notification Center): Ab 0.2.3 ist sie standardmäßig aus und erscheint nur mit installiertem, aktivem Plugin und gewählter Platzierung (siehe `docs/NOTIFICATION-CENTER.md`). Wer von 0.2.2 aktualisiert, behält seine gespeicherte Platzierung. Ohne das Plugin bleibt die Glocke trotzdem unsichtbar.

## Widerruf

JTL-Shop hat ab Version 5.7 ein eigenes Widerrufsformular. Das Theme zeigt deshalb standardmäßig keinen zweiten Widerrufs-Link. Das Feld „Adresse der Widerrufs-Seite“ bleibt leer. Nur wer eine eigene Widerrufsseite hat, trägt dort ihren Pfad ein, zum Beispiel `/widerruf`. Dann erscheint der Link „Vertrag widerrufen“ im Footer.

## Dateien aus dem Generator

`template.xml`, `snippets/mt-config.tpl` und `mt/*` (außer `mt-jtl.css`, `mt-legal.*` und `mt-notification-center.*`) werden aus dem Optionskatalog des Repos MichaThemeV5-Pro erzeugt. Nicht von Hand ändern.

## Erweiterungen

Dieses Repo enthält nur die kostenlosen Funktionen (Free). Pro-Funktionen und Erweiterungen werden separat und privat ausgeliefert, nicht über dieses Repo (https://theme.michael-gahn.de, Konto erforderlich). Ein optionaler Lizenzschlüssel kann in den Theme-Einstellungen unter „Lizenz“ eingetragen werden; ohne Schlüssel läuft das Theme vollständig.

## Recht: Felder und Hinweise

Artikelattribute (JTL-Wawi, Artikel, Attribute; Funktionsattribute gleichen Namens gehen auch): `mt_gpsr_manufacturer`, `mt_gpsr_responsible_person`, `mt_gpsr_contact`, `mt_gpsr_safety_info` und `mt_niedrigster_preis_30` (Text, z. B. 19,90 €; erscheint nur bei Streichpreis). Steuer- und Versandhinweis, Lieferzeit, Grundpreis, die Widerrufsfunktion (ab 5.7) und das EU-Label (ab 5.8) kommen von JTL bzw. NOVA und werden nicht doppelt ausgegeben. Für Videos mit 2-Klick den Baustein `snippets/mt-video-consent.tpl` einbinden. Block-Namen stammen aus NOVA 5.8.2 und sind im Shop zu prüfen. Technische Hinweise, keine Rechtsberatung. Texte und Einsatz anwaltlich prüfen lassen. Eigene Cookie-Banner liefert das Theme nicht; den Bestellbutton beschriftet die Kasse bzw. ein Rechtsplugin (Button-Lösung § 312j BGB).
