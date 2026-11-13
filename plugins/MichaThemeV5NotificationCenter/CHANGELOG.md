# Änderungen – MGD Notification Center (JTL-Shop 5)

## 0.7.1 – 13.11.2026

Behoben:

- Meldungen, die im Reiter „Meldungen“ ohne Ablaufdatum gespeichert wurden, erschienen nicht in der Glocke. JTL speicherte das leere Datum als 0000-00-00 statt „kein Datum“. Neue Meldungen speichern jetzt wirklich „kein Datum“, und beim Update repariert eine Migration die schon gespeicherten Meldungen.

## 0.7.0 – 12.11.2026

Neu:

- Reiter „Meldungen“ im Plugin: Meldungen direkt im JTL-Admin schreiben, bearbeiten und archivieren. Öffentlich oder für einen Kunden (Kundennummer oder E-Mail), mit Link, Start- und Ablaufdatum, Entwurf/Veröffentlicht/Archiviert. Im Text sind einfache Formatierung und Links erlaubt, alles andere wird entfernt.
- Die Versionsnummer ist jetzt auf allen Systemen gleich (0.7.0).

Behoben:

- Glocke im Theme: Fehlermeldungen erscheinen jetzt im Fenster „Alle anzeigen“, wenn es offen ist.

## 0.3.0 – 12.11.2026

Neu:

- Einstellungen im Plugin: „Anzahl im Panel“ (3 bis 30) und „Icon der Glocke“ (10 eingebettete Icons, kein Emoji).
- „Alle anzeigen“: unten im Panel öffnet ein Fenster mit allen Meldungen. Überschriften untereinander, ein Klick klappt den ganzen Inhalt samt Links auf.
- „Alle gelesen“ markiert wirklich alle Meldungen.
- „Ausblenden“ je Meldung und „Gelesene ausblenden“. Persönliche Meldungen merkt sich der Shop, öffentliche der Browser.
- Gelesen/Ausblenden läuft über die JTL-Schnittstelle (IO) und nur mit gültigem Sicherheits-Token. Es betrifft nur eigene, gerade sichtbare Meldungen.

- Update-Bereich: Nach dem Download steht jetzt korrekt „Paket heruntergeladen und geprüft (SHA-256, Inhalt)“. Im einfachen Modus gibt es keine Signatur-Prüfung, das wurde vorher falsch angezeigt.

Wichtig: Die neue Glocke steckt im Theme MichaThemeV5 (ab der nächsten Theme-Version). Mit einem älteren Theme bleibt die einfache Liste.

## 0.2.1 – 11.11.2026

Behoben:

- Update-Bereich: Eine veraltete PHP-Funktion (`curl_close`) wird nicht mehr aufgerufen. Unter PHP 8.5 gibt es dadurch keine Warnung mehr.
- Das Download-Paket hat jetzt feste Dateirechte (Ordner 755, Dateien 644). Vorher entstanden beim Entpacken auf manchen Servern gesperrte Ordner (700).

## 0.2.0 – 11.11.2026

Neu:

- Bereich „Updates“ im Plugin-Menü. Das Plugin kann jetzt selbst nachsehen, ob es eine neue Version gibt. Die Prüfung ist zuerst aus und muss einmal eingeschaltet werden. Es wird nie automatisch etwas installiert.
- Bereich „Übersicht“ im Plugin-Menü. Er zeigt, ob die Tabellen angelegt sind und wie viele Meldungen gerade sichtbar sind.
- Die Tabellen werden bei der Installation automatisch angelegt. Die Datei `database/schema.sql` muss nicht mehr von Hand ausgeführt werden.

Geändert und behoben:

- Persönliche Meldungen mit abgelaufenem Datum werden nicht mehr angezeigt (bisher galt das Ablaufdatum nur für öffentliche Meldungen).
- Höchstens 20 Meldungen je Liste, die neuesten zuerst.
- Anzeigename im Shop: „MGD Notification Center“. Die Plugin-ID `MichaThemeV5NotificationCenter` bleibt gleich, damit das Theme die Glocke weiter findet.
- Bei der Deinstallation bleiben die Meldungen in der Datenbank erhalten.

Geprüft: Installation im Demo-Shop jtl.theme.michael-gahn.de mit dem JTL-Installer (Ergebnis OK, Tabellen angelegt, beide Menüpunkte da), Übersicht und Bridge im Demo-Shop, 40 automatische Prüfungen (Bridge mit Test-Datenbank, Updates-Bereich). Nicht geprüft: der Bereich „Updates“ mit echter Admin-Anmeldung, und echte Meldungen in der Glocke.

## 0.1.0 – 23.10.2026

- Erster Entwurf: Tabellen für öffentliche und persönliche Meldungen, Bridge für das Theme.
