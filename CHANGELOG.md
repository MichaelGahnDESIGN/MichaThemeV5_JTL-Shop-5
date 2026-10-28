# Änderungen

Alle Versionen sind Pre-Alpha (frühe Testversion).

## 0.4.1

Kleine Korrektur zu 0.4.0 und bessere Anleitung.

- Korrigiert: Zähl-Texte für Screenreader stimmen jetzt in der Einzahl. Bei genau einem Favoriten heißt es „Favoriten, 1 Eintrag“, bei mehreren „Favoriten, 2 Einträge“ (vorher stand bei einem Favoriten „1 Einträge“). Auch beim Lesezeichen der Merkliste steht jetzt das Einzahl-Wort der Sprache, wenn genau ein Artikel drauf liegt.
- Doku: `README.md` und `docs/INSTALL.md` erklären Icon-Leiste und Farben Schritt für Schritt. Der Colorpicker ist fertig, aber noch nicht im Theme sichtbar (kommt mit den Plugin-Einstellungsseiten in Version 0.5.0).

## 0.4.0

Neu: Icon-Leiste in der Kopfzeile. Von links nach rechts: Hell/Dunkel-Schalter, Benachrichtigungen (Glocke), Merkliste, Favoriten, Konto, Warenkorb. Die Suche bleibt in der Mitte.

- Hell/Dunkel-Schalter im Header ist jetzt nur noch ein Symbol (44 x 44 Pixel, ohne Text). Der aktuelle Zustand steht im Tooltip und für Screenreader im Label (zum Beispiel „Farbmodus: Hell. Zum Wechseln klicken.“).
- Neue Einstellung „Ort des Hell/Dunkel-Schalters“ (Farben): Header (Standard), Footer oder Benutzerkonto. Der Schalter erscheint nur an dem gewählten Ort. Footer: eine Zeile „Farbmodus“ im Fußbereich. Benutzerkonto: ein Eintrag im Konto-Menü der Kopfzeile und in der Konto-Übersicht. Er ist dort nur für angemeldete Kunden sichtbar.
- Merkliste: Das ist die normale JTL-Wunschliste. Das Symbol (Lesezeichen, nicht Herz) erscheint nur, wenn mindestens ein Artikel auf der Liste steht. Die Zahl daneben ist grau, nicht rot. Das Symbol gibt es nur, wenn NOVA die Wunschliste selbst zeigt (angemeldeter Kunde, Wunschliste in JTL eingeschaltet).
- Favoriten (neu, Einstellung „Favoriten (Herz-Liste) anzeigen“, Standard an): Besucher können auf Produktkarten (Liste, Slider, Suche) und auf der Artikelseite ein Herz setzen. Die Liste liegt nur im Browser (localStorage), nichts geht an den Shop. Das Herz-Symbol im Header erscheint erst, wenn es mindestens einen Favoriten gibt.
- Glocke: neue Platzierung „In der Icon-Leiste (Header)“. Die Glocke erscheint nur mit aktivem Plugin und mindestens einem echten Eintrag, sonst ist sie unsichtbar (vorher stand dort „Keine neuen Benachrichtigungen“). Standard bleibt aus. Zähler grau statt rot, Panel schließt mit Escape.
- Die Vergleichsliste bleibt wie in NOVA. Sie ist weder Merkliste noch Favoriten.
- Handy (390 Pixel): Die Symbole bleiben 44 Pixel groß. Wenn die Leiste nicht neben das Logo passt, rutscht sie in eine zweite Zeile. Es wird nichts abgeschnitten.
- Update von 0.3.x: Die beiden neuen Einstellungen bekommen beim ersten Seitenaufruf automatisch ihren Standardwert. Wer vorher schon Einstellungen gespeichert hat, behält alle bisherigen Werte.

Ehrlich gesagt: Die Templates wurden im Demo-Shop (JTL 5.8.2) mit der echten Smarty-Engine übersetzt und die Kopfzeile mit dem echten Template-Code erzeugt; die Bilder dazu sind eine Vorschau mit dem NOVA-Stylesheet. Der Demo-Shop steht im Wartungsmodus, die fertigen Shop-Seiten wurden deshalb nicht im Browser aufgerufen.

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
