# Änderungen

Alle Versionen sind Pre-Alpha (frühe Testversion).

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
