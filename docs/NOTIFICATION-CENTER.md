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
4. Die Datei `plugins/MichaThemeV5NotificationCenter/database/schema.sql` einmal in der Shop-Datenbank ausführen (zum Beispiel mit phpMyAdmin, Reiter „SQL“). Das Plugin legt seine Tabellen nicht selbst an. Ohne diesen Schritt zeigt das Theme die Glocke nicht, weil die Tabellen fehlen.
5. Eine Platzierung wählen (siehe oben) und den Shop-Cache leeren.

## Wichtig zu wissen

- Das Plugin ist ein früher Entwurf (Version 0.1.0). Es wurde noch nicht in einem laufenden Shop installiert und getestet.
- Das Plugin lässt sich nicht automatisch einrichten. Das Anlegen der Tabellen (`schema.sql`) bleibt vorerst ein Handgriff.
- Eine Oberfläche zum Schreiben von Benachrichtigungen gibt es noch nicht. Die Meldungen müssen direkt in der Datenbank angelegt werden (Tabelle `xplugin_mgd_notifications`).
- Die Tabellen trennen Meldungen nach Shop-Kunde. Es gehen keine Daten an Dritte.
- Ist das Plugin aktiv, aber es gibt noch keine Meldungen, zeigt die Glocke „Keine neuen Benachrichtigungen“.
