# MGD Suche für JTL-Shop 5

## Zweck

MGD Suche verbessert die bestehende JTL-Shop-5-Suche, ohne ihre Preis-, Sichtbarkeits-, Kundengruppen- oder Cachelogik zu ersetzen. Die Erweiterung erkennt auf einer Suche passende Merkmalwerte in der aktiven Shopsprache und zeigt sie als direkte, native JTL-Filterlinks.

Beispiel: Sucht ein Besucher nach `Baumwolle` und dieser Wert ist als JTL-Merkmal gepflegt, erscheint ein Link wie `Material: Baumwolle`. Der Klick führt zu JTLs eigener Merkmalfilteransicht.

## Funktionsumfang 0.1.0

- Einstellungen im JTL-Backend für Aktivierung, Abgleichart, Mindestlänge, Ergebnislimit, Darstellung und Akzentfarbe
- exakter, optionaler Präfix-Abgleich von Merkmalwerten in der aktuellen Shopsprache
- keine Änderung an JTL-Kerndateien, keine externe Suche, keine Cookies, kein LocalStorage und kein Tracking
- lokale CSS- und JavaScript-Dateien; die JavaScript-Datei versendet keine Anfragen
- barrierearme Links mit sichtbarem Tastaturfokus und ausreichend großen Touch-Zielen
- Backend-Punkt **Weitere Plugins** mit Installationsstatus für MGD-JTL-Plugins und zentraler, validierter Katalog-JSON mit lokalem Fallback

## Grenzen der ersten Version

JTL-Shop 5 besitzt zwar dokumentierte Suchparameter und Merkmalfilter, aber keinen dokumentierten Erweiterungspunkt, der die nativen Volltexttreffer sicher zu einer gemeinsamen OR-Suche mit Merkmalwerten umbaut. Deshalb wird ein Merkmal nicht still in die Rangfolge der Standardergebnisse gemischt. Stattdessen bleibt das native Ergebnis unverändert und passende Merkmale werden als zusätzliche, nachvollziehbare Filtertreffer angeboten.

Ein vollständiger eigener Suchindex mit Ranking, Tippfehlertoleranz und Live-Vorschlägen wäre ein separates, deutlich größeres Suchsystem. Er soll erst nach einer Kompatibilitätsprüfung gegen reale JTL-Shop-Versionen und Produktdaten umgesetzt werden.

## Installation

1. Den Ordner `MGDSucheJTLShop` als ZIP verpacken.
2. In JTL-Shop unter **Plugins → Pluginverwaltung** hochladen, installieren und aktivieren.
3. Unter **Plugins → MGD Suche – Einstellungen** die gewünschte Darstellungs- und Abgleichart wählen.
4. Für sichtbar filterbare Merkmale zusätzlich die JTL-eigenen Merkmalfilter unter **Darstellung → Standardelemente → Suche & Artikellisten** konfigurieren.

## Datenschutz

Die Erweiterung verarbeitet den eingegebenen Suchbegriff nur innerhalb der aktuellen PHP-Anfrage, um Merkmalwerte aus der Shopdatenbank zu lesen. Sie speichert selbst keine Suchbegriffe, Besucherkennungen oder IP-Adressen und überträgt keine Daten an Dritte. Das ersetzt keine individuelle Datenschutzprüfung des gesamten Shops.

Der Bereich **Weitere Plugins** ruft ausschließlich beim Öffnen im Backend eine öffentliche Produktliste von `theme.michael-gahn.de` ab. Enthalten sind keine Shop-, Konto- oder Besucherdaten. Ist der Katalog nicht erreichbar, nutzt das Plugin eine lokale Fallback-Liste.

## Prüfung

Die reine Suchbegriffnormalisierung lässt sich ohne JTL-Installation prüfen:

```bash
php plugins/MGDSucheJTLShop/tests/SearchTermNormalizerTest.php
```

Für die Installation muss zusätzlich im Testshop geprüft werden: exakte Suche, Präfixsuche, mehrere Sprachen, deaktivierte Merkmale, mobile Darstellung und ein Child-Template.
