# Änderungen – MGD Notification Center (JTL-Shop 5)

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
