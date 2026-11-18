# MGD Suche für JTL-Shop 5

## Zweck

MGD Suche verbessert die bestehende JTL-Shop-5-Suche, ohne ihre Preis-, Sichtbarkeits-, Kundengruppen- oder Cachelogik zu ersetzen. Die Erweiterung erkennt auf einer Suche passende Merkmalwerte in der aktiven Shopsprache und zeigt sie als direkte, native JTL-Filterlinks.

Beispiel: Sucht ein Besucher nach `Baumwolle` und dieser Wert ist als JTL-Merkmal gepflegt, erscheint ein Link wie `Material: Baumwolle`. Der Klick führt zu JTLs eigener Merkmalfilteransicht.

## Funktionsumfang 1.5.0

- Das Plugin verändert das Suchformular des Shops nicht. Es zeigt nur auf der Ergebnisseite passende Merkmale an
- Reiter im Backend in dieser Reihenfolge: **Einstellungen**, **Updates**, **Plugin-Empfehlungen**
- Sicherheitsnetz: Ein Fehler im Plugin schaltet nur das Plugin für diesen Seitenaufruf ab, der Shop läuft weiter. Der Fehler steht im JTL-Systemlog (Eintrag beginnt mit `MGDSucheJTLShop:`)
- Einstellungen im JTL-Backend für Aktivierung, Abgleichart, Mindestlänge, Ergebnislimit, Darstellung und Akzentfarbe
- exakter, optionaler Präfix-Abgleich von Merkmalwerten in der aktuellen Shopsprache
- keine Änderung an JTL-Kerndateien, keine externe Suche, keine Cookies, kein LocalStorage und kein Tracking
- lokale CSS- und JavaScript-Dateien; die JavaScript-Datei versendet keine Anfragen und fügt nichts in die Seite ein
- barrierearme Links mit sichtbarem Tastaturfokus und ausreichend großen Touch-Zielen
- Backend-Punkt **Plugin-Empfehlungen** als visuelle MGD-Plugin-Kollektion: Kategorie, Bild, Kurzbeschreibung, Funktionsliste, Version, Preisstatus, Installationsstatus und sichere Handlungslinks
- zentrale, validierte Katalog-JSON von `theme.michael-gahn.de` mit lokaler Ausfallsicherung
- opt-in-basierte Updateprüfung im JTL-Backend: akzeptiert ausschließlich das erwartete ZIP eines stabilen GitHub-Releases und verweist für die Installation auf die JTL-Pluginverwaltung

## Grenzen der ersten Version

JTL-Shop 5 besitzt zwar dokumentierte Suchparameter und Merkmalfilter, aber keinen dokumentierten Erweiterungspunkt, der die nativen Volltexttreffer sicher zu einer gemeinsamen OR-Suche mit Merkmalwerten umbaut. Deshalb wird ein Merkmal nicht still in die Rangfolge der Standardergebnisse gemischt. Stattdessen bleibt das native Ergebnis unverändert und passende Merkmale werden als zusätzliche, nachvollziehbare Filtertreffer angeboten.

Ein vollständiger eigener Suchindex mit Ranking, Tippfehlertoleranz und Live-Vorschlägen wäre ein separates, deutlich größeres Suchsystem. Er soll erst nach einer Kompatibilitätsprüfung gegen reale JTL-Shop-Versionen und Produktdaten umgesetzt werden.

## Installation

1. Den Ordner `MGDSucheJTLShop` als ZIP verpacken.
2. In JTL-Shop unter **Plugins → Pluginverwaltung** hochladen, installieren und aktivieren.
3. Unter **Plugins → MGD Suche – Einstellungen** die gewünschte Darstellungs- und Abgleichart wählen.
4. Für sichtbar filterbare Merkmale zusätzlich die JTL-eigenen Merkmalfilter unter **Darstellung → Standardelemente → Suche & Artikellisten** konfigurieren.

## Datenschutz

Die Erweiterung verarbeitet den eingegebenen Suchbegriff nur innerhalb der aktuellen PHP-Anfrage, um Merkmalwerte aus der Shopdatenbank zu lesen. Sie speichert selbst keine Suchbegriffe, Besucherkennungen oder IP-Adressen und überträgt keine Besucherdaten an Dritte. Was das Plugin selbst an theme.michael-gahn.de meldet, steht im Abschnitt „Anmeldung bei theme.michael-gahn.de“. Das ersetzt keine individuelle Datenschutzprüfung des gesamten Shops.

Der Bereich **Plugin-Empfehlungen** ruft ausschließlich im Backend eine öffentliche, plattformspezifische Liste von `theme.michael-gahn.de` ab. Sichtbarkeit, Texte, Reihenfolge und Version werden zentral gepflegt; die Darstellung erfolgt mit lokalen Symbolen und ohne externe Bilder. Enthalten sind keine Shop-, Konto- oder Besucherdaten. Das Plugin akzeptiert ausschließlich `schema: 1`, die eigene Plattform und die fest verdrahtete Detailseite von `theme.michael-gahn.de`; fremde Links aus dem Feed werden verworfen. Ist der Katalog nicht erreichbar, nutzt das Plugin nur einen zuletzt lokal gespeicherten Stand.

## Updates

Unter **Updates** (MGD Update Client) kannst du auf neue Versionen prüfen. So geht es:

1. Öffne im Backend **Plugins → MGD Suche → Updates**.
2. Nach Updates wird ohne dein Zutun nicht gesucht. Erst nach **Update-Prüfung einschalten** oder **Jetzt prüfen** fragt das Plugin den Update-Server von Michael Gahn DESIGN nach neuen Versionen. Dabei sieht der Server die IP-Adresse deines Shops, die Domain, die Plugin-Version und die Shop-Version. Keine Besucher-, Bestell- oder Kundendaten.
3. Gibt es eine neue Version, kannst du das Paket herunterladen. Das Plugin prüft es dabei (SHA-256-Prüfsumme, Inhalt) und legt es in einen gesperrten Ordner.
4. Installieren musst du selbst: ZIP in der JTL-Pluginverwaltung hochladen. Das Plugin tauscht keine Dateien aus und aktiviert nichts.

Der Bereich braucht das Recht für Plugins und prüft bei jeder Aktion das JTL-Sicherheits-Token.

So wird geprüft (einfacher Modus): Auf theme.michael-gahn.de liegt die Datei `plugin-updates/mgd-suche.json` mit Version und Prüfsumme (SHA-256). Das ZIP selbst kommt von GitHub (nur vom Konto MichaelGahnDESIGN). Das Plugin rechnet die Prüfsumme nach, prüft die Zip-Inhalte und legt das ZIP erst dann in den gesperrten Ordner. Stimmt etwas nicht, gibt es keinen Download.

⚠️ Das ist eine Prüfsumme, keine Signatur. Wer GitHub und den Webspace gleichzeitig übernimmt, könnte ein falsches ZIP anbieten. Für Free-Plugins ist das ein bewusster Kompromiss. Die signierte Variante bleibt vorbereitet (Schlüssel fehlen noch).

Die frühere GitHub-Release-Abfrage und die Einstellung „GitHub-Updatehinweise“ sind entfallen.

## Anmeldung bei theme.michael-gahn.de

Seit Version 1.5.0 meldet sich MGD Suche bei theme.michael-gahn.de an. So weiß Michael Gahn DESIGN, wie viele Shops das kostenlose Plugin nutzen.

**Wann meldet es sich?**

1. Einmal beim Installieren, Aktivieren und Aktualisieren.
2. Danach höchstens einmal am Tag, wenn jemand das JTL-Backend öffnet. Im Shop selbst (bei deinen Besuchern) passiert nie etwas.

**Was wird gesendet?** Nur Plugin (`mgd-suche`), Version, Shopsystem (`jtl5`), Domain deines Shops und eine zufällige ID. Keine Kunden-, Bestell- oder Besucherdaten, keine E-Mail.

**Abschalten:** Im Backend **Plugins → MGD Suche → Updates** den Haken bei „Installation bei Michael Gahn DESIGN melden“ entfernen und speichern. Dann wird der Eintrag auf der Plattform gelöscht, und es wird nichts mehr gesendet. Ab Werk ist der Haken gesetzt.

Gut zu wissen:

- Die Anmeldung läuft auch, wenn die Update-Prüfung aus ist. Die Update-Prüfung bleibt trotzdem aus, bis du sie einschaltest.
- Ist theme.michael-gahn.de nicht erreichbar, läuft das Plugin ganz normal weiter. Es versucht es später noch einmal (höchstens einmal am Tag).
- Die Verbindung wartet höchstens 5 Sekunden.

## Prüfung

Die reine Suchbegriffnormalisierung und die Katalog-Validierung lassen sich ohne JTL-Installation prüfen:

```bash
php plugins/MGDSucheJTLShop/tests/SearchTermNormalizerTest.php
php plugins/MGDSucheJTLShop/tests/PluginCatalogProviderTest.php
php plugins/MGDSucheJTLShop/tests/UpdateSmokeTest.php
php plugins/MGDSucheJTLShop/tests/SafetyNetTest.php
```

Für die Installation muss zusätzlich im Testshop geprüft werden: exakte Suche, Präfixsuche, mehrere Sprachen, deaktivierte Merkmale, mobile Darstellung und ein Child-Template.
