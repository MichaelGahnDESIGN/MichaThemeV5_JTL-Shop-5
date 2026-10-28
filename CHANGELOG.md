# Änderungen

Alle Versionen sind Pre-Alpha (frühe Testversion).

## 0.3.1

Geändert (kleine Korrektur):

- Der Text der Ankündigungsleiste ist jetzt auch in `mt/options.json` standardmäßig leer (keine erfundene Versandgrenze).
- Link-Hover nutzt die Textfarbe der Akzentfarbe (aus dem gemeinsamen Kern), im Theme-CSS bleibt der Hover gleich.
- Prozent-Siegel und Countdown sind in den Kern-Einstellungen standardmäßig aus (im kostenlosen JTL-Theme nicht enthalten).

Geprüft: Build aus dem Generator, Free-Prüfung, Versionen, Tests im Pro-Repo.

## 0.3.0

Neues Design (siehe `docs/INSTALL.md`, Abschnitt „Neues Aussehen“):

- Farben: Hell ist Weiß und Hellgrau, Dunkel ist Anthrazit. Standard-Akzent ist Rot, die anderen Akzente und die eigene Akzentfarbe bleiben wählbar. Wer schon eine Akzentfarbe gespeichert hat, behält sie.
- Kopfzeile: Logo links, Suche mittig (höchstens 40 rem breit), Konto, Warenkorb und Hell/Dunkel-Schalter rechts, Menü in zweiter Zeile (`Menü in eigener Zeile` ist jetzt standardmäßig an). Der Schalter sitzt bei den Icons, nur im Bestellvorgang bleibt die eigene Zeile.
- Buttons: `btn-primary` in Akzentfarbe, alle anderen (`btn-secondary`, `btn-outline-secondary`, `btn-outline-primary`, `btn-light`, `btn-info`) neutral umrandet. Gefahr-, Erfolg- und Warn-Buttons getönt.
- Siegel: Sale (`ribbon-2`) blassrot mit Text, Neu (`ribbon-3`) dunkel, Ausverkauft (`ribbon-7`) grau umrandet. Nie weiße Schrift auf Rot. Vorher griffen die Siegel-Regeln des Themes nicht (falsche Klassennamen).
- Produktkarten: Rahmen, kein Hochspringen mehr, Hover hell mit Schatten, dunkel mit hellerer Fläche. Preise fett mit gleich breiten Ziffern, Streichpreis grau und durchgestrichen.
- Formulare, Hinweise (Fehler mit dem Wort „Fehler:“), Breadcrumb, Seitenzahlen, Tabs, Tabellen, Dropdowns, Footer (graue Fläche), Cookie-Banner von JTL: alles über die Theme-Farben, hell und dunkel.
- Fokus: neutraler Ring in Textfarbe statt gelb.
- Glocke (Notification Center): feste Farben durch Theme-Farben ersetzt.
- Schriftgewichte: nur 400 und 700.
- Vorschaubild `preview.png` neu (fehlte, `template.xml` verwies darauf).
- Hell/Dunkel-Schalter speichert den Modus erst beim Klick (nicht schon beim Laden). Eigene Akzentfarbe: bessere Schriftfarbe auf dem Button und abgeleitete Hover-Farbe.
- Weniger erfundene Texte: Ersatz-Lieferzeit und Text der Ankündigungsleiste sind standardmäßig leer.

Geprüft: Vorschau mit dem echten NOVA-Stylesheet 1.8.0 und dem Theme-CSS (hell und dunkel, 1280 und 390 Pixel, Akzent Rot, Indigo und eine eigene Farbe), Kontraste der Farben rechnerisch (Test im Pro-Repo), alle Templates im Demo-Shop mit der echten Smarty-Engine übersetzt (JTL-Shop 5.8.2), Free-Prüfung und Build aus dem Generator. Nicht geprüft: die fertigen Shop-Seiten live (Demo-Shop im Wartungsmodus).

## 0.2.3

Geändert und behoben:

- Notification Center: Die Glocke ist jetzt standardmäßig aus. Bisher erschien sie unten rechts, auch wenn das Plugin gar nicht installiert war, und blieb dann leer. Jetzt erscheint sie nur, wenn das Plugin `MichaThemeV5NotificationCenter` aktiv ist und eine Platzierung gewählt wurde. Ohne Plugin werden auch das dazugehörige CSS und JavaScript nicht mehr geladen. Wer von 0.2.2 aktualisiert, behält seine gespeicherte Platzierung; ohne Plugin ist die Glocke trotzdem weg.
- Die Auswahl „Nur per Template-Einbindung“ heißt jetzt „Aus (keine Glocke)“.
- Dokumentation zur Glocke ehrlicher: Das Plugin muss separat installiert werden, die Datei `schema.sql` muss einmal von Hand ausgeführt werden, und es gibt noch keine Oberfläche zum Schreiben von Meldungen.

Neu:

- Datei `LICENSE` und Abschnitt „Lizenz“ in der README: Alle Rechte vorbehalten, Schriften unter der SIL Open Font License, Fremdcode behält seine Lizenz. Keine Rechtsberatung; die Lizenzfrage wird noch rechtlich geprüft.

Geprüft: Aufbau und Syntax der Dateien (PHP, Smarty, XML, JSON), Free-Prüfung (keine Pro-Dateien) und der Build aus dem Generator (er ändert in diesem Repo nur die Versionsnummer). Das Plugin wurde weiterhin nicht in einem Shop installiert.

## 0.2.2

Neu seit 0.2.1:

- Paket „Recht“: Hinweis neben dem Preis, Ersatz-Lieferzeit, niedrigster Preis der letzten 30 Tage (aus einem Artikelattribut), Gewährleistungshinweis im Artikel und im Warenkorb, Produktsicherheit (GPSR) auf der Artikelseite, „Ausverkauft“-Kennzeichnung, Link zur Barrierefreiheitserklärung im Footer.
- Video mit 2 Klicks (YouTube, Vimeo): Es wird erst nach dem Klick etwas von Dritten geladen.
- Notification Center: Glocke im Theme, Platzierung einstellbar, dazu ein Plugin-Entwurf (siehe `docs/NOTIFICATION-CENTER.md`).

Geändert und behoben:

- Widerruf: Die Adresse der Widerrufs-Seite ist standardmäßig leer, weil JTL-Shop ab 5.7 ein eigenes Widerrufsformular hat. Der Link im Footer erscheint nur, wenn eine gültige Adresse eingetragen ist.
- Dunkelmodus: Das Theme-CSS wird nach dem NOVA-CSS geladen. Link-Farben in Header und Footer haben im Dunkelmodus wieder genug Kontrast.
- Barrierefreiheit: Das Video-Fenster (`iframe`) hat jetzt immer einen Titel („YouTube Video“ bzw. „Vimeo Video“, wenn kein eigener Titel gesetzt ist).

Geprüft: Aufbau und Syntax der Dateien (PHP, JavaScript, Smarty, XML, JSON) und die Free-Prüfung (keine Pro-Dateien). Alle Templates wurden im Demo-Shop mit JTL-Shop 5.8.2 mit der echten Smarty-Engine übersetzt, ohne Fehler. Die Seiten selbst wurden mit dieser Version nicht neu angesehen, weil der Demo-Shop im Wartungsmodus ist. Das Plugin wurde nicht in einem Shop installiert.

## 0.2.1

- Aktivieren in JTL-Shop 5.8 ohne Fehler HTTP 500 (Template-Bootstrap, Abschnitt `theme` in `template.xml`).
- Nur Free-Funktionen.
