# Notification Center (Glocke)

Das Theme kann eine Glocke mit Benachrichtigungen zeigen. Seit Version 0.2.3 ist sie standardmäßig aus.

## Wann erscheint die Glocke?

Die Glocke erscheint nur, wenn beides stimmt:

1. Das Plugin `MichaThemeV5NotificationCenter` ist im Shop installiert und aktiv.
2. Im Admin-Bereich ist eine Platzierung gewählt: Darstellung, Template, MichaThemeV5, Einstellungen, Benachrichtigungen, Platzierung. Standard ist „Aus (keine Glocke)“.

Fehlt eines von beiden, wird weder die Glocke noch das dazugehörige CSS und JavaScript geladen. Das gilt auch, wenn die Platzierung schon auf „Schwebend unten rechts“ steht (zum Beispiel von Version 0.2.2): Ohne aktives Plugin bleibt die Glocke unsichtbar.

## So schalten Sie die Glocke ein

1. Vorher eine Datenbank-Sicherung machen und zuerst in einem Test-Shop ausprobieren.
2. Den Ordner `plugins/MichaThemeV5NotificationCenter` in den Ordner `<Ihr Shop>/plugins/` kopieren.
3. Im Admin-Bereich: Plugins, Plugin-Verwaltung. Das Plugin „MichaThemeV5 Notification Center“ installieren und aktivieren.
4. Ab Version 0.2.0 legt das Plugin seine Tabellen bei der Installation selbst an. Prüfen können Sie das im Plugin unter „Übersicht“. Nur wenn dort „Die Tabellen des Plugins fehlen“ steht: die Datei `plugins/MichaThemeV5NotificationCenter/database/schema.sql` einmal in der Shop-Datenbank ausführen (zum Beispiel mit phpMyAdmin, Reiter „SQL“).
5. Eine Platzierung wählen (siehe oben) und den Shop-Cache leeren.

## Einstellungen (ab 0.3.0)

1. Admin-Bereich: Plugins, MGD Notification Center, Reiter „Einstellungen“.
2. „Anzahl im Panel“: So viele aktuelle Meldungen zeigt die Glocke. Alle weiteren stehen unter „Alle anzeigen“.
3. „Icon der Glocke“: eines von 10 eingebetteten Icons wählen.
4. Besucher können Meldungen ausblenden, alle als gelesen markieren und gelesene ausblenden.

## Updates

1. Im Admin-Bereich: Plugins, MGD Notification Center, Reiter „Updates“.
2. Die Update-Prüfung einmal einschalten. Vorher fragt das Plugin nirgends nach.
3. Gibt es eine neue Version, steht sie dort. Das Plugin lädt das Paket nur auf Ihren Klick und prüft es. Installiert wird es von Ihnen, wie jedes Plugin.

## Paket bauen (für Entwickler)

1. Im Repo-Ordner ausführen: `tools/build-notification-zip.sh <Tag-oder-Commit> <Ausgabeordner>`
2. Beispiel: `tools/build-notification-zip.sh origin/main /tmp/nc`
3. Heraus kommen das ZIP und eine `.sha256`-Datei. Die Version liest das Skript aus `info.xml`.
4. Im ZIP stehen feste Rechte: Ordner 755, Dateien 644. So stimmen die Rechte auch nach dem Entpacken auf dem Server. Der Ordner `tests/` kommt nicht mit ins Paket.

## Wichtig zu wissen

- Das Plugin ist noch jung (Version 1.4.0). Es wurde im Demo-Shop installiert. Mit echten Meldungen wurde die Glocke dort noch nicht getestet.
- Bei der Deinstallation bleiben die Meldungen in der Datenbank erhalten.
- Meldungen schreiben Sie im Plugin unter „Meldungen“ (ab 0.7.0): Titel, Text, Link, öffentlich oder für einen Kunden, Start- und Ablaufdatum.
- Die Tabellen trennen Meldungen nach Shop-Kunde. Es gehen keine Daten an Dritte.
- Ab 0.4.0: Ist das Plugin aktiv, aber es gibt noch keine Meldungen, ist die Glocke unsichtbar (vorher stand dort „Keine neuen Benachrichtigungen“). Sie erscheint erst mit mindestens einem Eintrag.
- Neue Platzierung „In der Icon-Leiste (Header)“: Die Glocke sitzt dann in der Kopfzeile zwischen Hell/Dunkel-Schalter und Merkliste. Das Panel schließt mit Escape oder mit einem Klick daneben. Der Zähler ist grau.
