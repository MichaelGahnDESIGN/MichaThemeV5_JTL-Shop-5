# Notification Center (Glocke)

Das Theme zeigt eine Glocke mit Benachrichtigungen. Wo sie erscheint, stellen Sie unter Einstellungen, Benachrichtigungen, Platzierung ein.

## Wichtig zu wissen

- Ohne das Plugin `MichaThemeV5NotificationCenter` ist die Glocke immer leer („Keine neuen Benachrichtigungen“). Das ist Absicht.
- Das Plugin liegt im Ordner `plugins/` dieses Repos. Es ist ein früher Entwurf (Version 0.1.0) und wurde noch nicht in einem laufenden Shop installiert und getestet.
- Das Plugin legt seine Tabellen nicht selbst an. Die Datei `plugins/MichaThemeV5NotificationCenter/database/schema.sql` muss von Hand ausgeführt werden. Vorher in einem Test-Shop prüfen und eine Datenbank-Sicherung machen.
- Eine Oberfläche zum Schreiben von Benachrichtigungen gibt es noch nicht. Die Meldungen müssen direkt in der Datenbank angelegt werden.
- Die Tabellen trennen Meldungen nach Shop-Kunde. Es gehen keine Daten an Dritte.
