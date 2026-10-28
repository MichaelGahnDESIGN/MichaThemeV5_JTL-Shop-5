# MichaThemeV5 für JTL-Shop 5

Theme von Michael Gahn DESIGN mit Hell-/Dunkelmodus, vielen Optionen und rechtssicherer Ausrichtung für DE und EU.

**Status:** in Entwicklung (Pre-Alpha).

## Funktionen

29 Free-Funktionen sind aufgeführt (fertig oder noch in Arbeit, den Stand zeigt die Liste). Dieses Repo enthält ausschließlich die kostenlosen Funktionen.
Die vollständige Liste mit Status steht in [`docs/FEATURES.md`](docs/FEATURES.md), offene Punkte in [`TODO.md`](TODO.md).

## Installation

Pre-Alpha: getestet nur mit JTL-Shop 5.8.2. Bitte zuerst in einem Test-Shop ausprobieren. Die Schritte stehen in [`docs/INSTALL.md`](docs/INSTALL.md).

**Glocke (Notification Center):** Standardmäßig aus. Sie erscheint nur, wenn zwei Dinge stimmen: Das Plugin `MichaThemeV5NotificationCenter` (Ordner `plugins/`) ist separat installiert und aktiv, und unter Einstellungen, Benachrichtigungen, Platzierung ist eine Platzierung gewählt. Das Plugin legt seine Tabellen nicht selbst an: Die Datei `plugins/MichaThemeV5NotificationCenter/database/schema.sql` muss einmal von Hand ausgeführt werden. Eine Oberfläche zum Schreiben von Meldungen gibt es noch nicht. Mehr dazu in [`docs/NOTIFICATION-CENTER.md`](docs/NOTIFICATION-CENTER.md).

## Aussehen (ab Version 0.3.0)

Hell: Weiß und Hellgrau. Dunkel: Anthrazit. Der Standard-Akzent ist Rot und lässt sich in den Theme-Einstellungen ändern. Rot steht nur bei bedienbaren Dingen (Kaufen-Button, Menüpunkt, Seitenzahl). Alle anderen Buttons sind neutral. Der Hell/Dunkel-Umschalter sitzt standardmäßig in der Kopfzeile.

## Farben und Colorpicker

Der Colorpicker ist ein Farbwähler. Er hat eine Farbfläche, einen Farbton-Regler und ein Feld für den HEX-Wert (zum Beispiel `#c8161a`) mit Kopieren-Knopf. Dazu kommt eine Palette für bis zu 12 gespeicherte Farben und eine Warnung, wenn Text auf deiner Akzentfarbe schlecht lesbar wäre.

Ehrlich gesagt: Der Colorpicker ist fertig und getestet, aber in diesem Theme noch nicht zu sehen. Er wird mit den Einstellungsseiten der Plugins in Version 0.5.0 eingebunden. Bis dahin wählst du die Akzentfarbe wie bisher: aus der Liste oder als eigene Farbe (HEX-Wert) in den Theme-Einstellungen.

## Icon-Leiste in der Kopfzeile (ab Version 0.4.0)

Von links nach rechts: Hell/Dunkel-Schalter, Glocke, Merkliste, Favoriten, Konto, Warenkorb. Die Suche bleibt in der Mitte.

- **Hell/Dunkel-Schalter:** In der Kopfzeile nur ein Symbol (ohne Text). Den Ort ändern: Darstellung, Template, MichaThemeV5, Einstellungen, Bereich Farben, „Ort des Hell/Dunkel-Schalters“ (Header, Footer oder Benutzerkonto).
- **Glocke:** Kommt vom Plugin MichaThemeV5NotificationCenter. Standard ist aus. Sie erscheint nur mit aktivem Plugin, gewählter Platzierung „In der Icon-Leiste (Header)“ und mindestens einem echten Eintrag.
- **Merkliste:** Das ist die normale JTL-Wunschliste. Das Symbol erscheint nur, wenn etwas drauf liegt (und NOVA die Wunschliste zeigt: angemeldeter Kunde, in JTL eingeschaltet).
- **Favoriten:** Eine eigene Herz-Liste im Browser des Besuchers. Nichts geht an den Shop. Abschaltbar bei „Favoriten (Herz-Liste) anzeigen“ im Bereich Header.

Mehr dazu: [`docs/INSTALL.md`](docs/INSTALL.md).

## Weitere Funktionen (Pro)

Dieses Repo enthält nur die kostenlosen Funktionen (Free). Pro-Funktionen und Erweiterungen werden separat und privat ausgeliefert und sind nicht Teil dieses Repos; Informationen, Kauf und Verwaltung unter https://theme.michael-gahn.de (Konto erforderlich).

## Lizenz

Alle Rechte vorbehalten, Copyright (c) 2026 Michael Gahn DESIGN (siehe Datei [`LICENSE`](LICENSE)). Das Theme ist kein Open Source.

- Alle Rechte vorbehalten heißt: Was nicht ausdrücklich erlaubt ist, ist ohne Rückfrage nicht erlaubt, zum Beispiel Weitergeben, Verkaufen oder verändert Veröffentlichen. Wer das Theme in einem Shop einsetzen möchte, fragt bitte vorher kurz nach.
- Die mitgelieferten Schriften stehen unter der SIL Open Font License (OFL). Die Lizenztexte liegen im Ordner `templates/MichaThemeV5/mt/fonts` (Dateien `LICENSE-*.txt`).
- JTL-Shop und das Eltern-Template NOVA gehören nicht zu diesem Repo und behalten ihre eigenen Lizenzen. Das Gleiche gilt für anderen Fremdcode.

Das ist keine Rechtsberatung; die Lizenzfrage wird noch rechtlich geprüft.

## Rechtliches

Alle rechtlichen Angaben sind technische Hinweise zum Stand der Erstellung und keine Rechtsberatung. Vor Veröffentlichung von einer Anwältin oder einem Anwalt prüfen lassen.
