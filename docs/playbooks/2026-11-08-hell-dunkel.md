# Playbook · JTL Hell-/Dunkelkorrektur

## Auftrag und Grenzen

Michael beauftragt die Korrektur der weißen Navigationsfläche und schlecht lesbarer Texte sowie die Prüfung beider Farbmodi. Eigener JTL-Testshop, Wartungsmodus bleibt aktiv. Keine Bestellung, Kundendaten, Änderung der Demo-Inhalte oder öffentlichen Plattformfreigabe. Vor gezielter Bereitstellung nur betroffene Dateien sichern. Keine neue GitHub-Veröffentlichung im Auftrag; bestehende 0.6.0-Pakete bleiben unverändert. Änderungen werden auf main dokumentiert.

Grundlinie: öffentliches JTL-Repository main d71aaa3, sauber; Testshop JTL 5.8.2, Theme/Begleitplugin 0.6.0. Kein eingerichteter Projektgraph. Handgepflegte JTL-Adapter liegen im Theme-Repository; generierte Kernassets bleiben unangetastet. Zugehörige Plattform-TODOs t-plugin-start-live und t-catalog-legal bleiben eigenständig.

| ID | Schritt | Voraussetzung / Erfolgskriterium | Rückfall | Status |
| --- | --- | --- | --- | --- |
| C1 | Echte DOM-/Farb-Grundlinie sichern | Screenshotfehler reproduziert; Selektoren, Kontrast und Farbumschalter erfasst | lesend | geprüft |
| C2 | Themenabhängige Navigation und Textkontrast korrigieren | Native Scrollfunktion erhalten; neutrale eingefügte Textfarben lesbar, absichtlich farbige Inhalte/Bildbereiche erhalten; keine Datenänderung | Git-Grundlinie | geprüft |
| C3 | Gezielte lokale Prüfung | JS-Syntax, Kontrast-/Neutralfarben-Regression, CSS und Smarty-Prüfung; keine neue Abhängigkeit | lokale Quellen | geprüft |
| C4 | Gesicherte Testshop-Bereitstellung | Exakte Dateiliste, Originale und SHA-256 gesichert; nur diese Dateien übertragen; Templatecache gezielt berücksichtigen | Originaldateien aus Manifest, neue Dateien ausschließlich anhand Positivliste entfernen | geprüft |
| C5 | Live-Matrix Hell/Dunkel | 390/768/1280 px; Navigation/Pfeile, Bildbereich und Text, Suche, Kategorie, Produkt, Reiter, Konto-/Warenkorbmenü, Fokus. Echte Kontrastwerte; Wartung bleibt aktiv | Dateisicherung | geprüft |
| C6 | Git und Wissen abgleichen | Tatsächliche Ergebnisse und Grenzen dokumentieren, Quellen auf main, AI_Knowledge aktualisieren | Dokumentationscommit | geprüft |

C1: NOVA-Pfeile verwenden weiße CSS-Verläufe. Die schlecht lesbare Passage stammt aus eingebetteten Word-Formatierungen mit color:#767171 auf dunklem Grund. Der Hintergrund ist ein natives OPC-Parallax-Element. Keine Ersetzung der veröffentlichten OPC-Inhalte.

## Ergebnisse

C2/C3: Vier gezielte Asset-/Headerdateien, keine Kern-/Token-Generierung. Sieben Node-Regressionstests und beide Syntaxprüfungen erfolgreich. Festes Word-Grau wird nur bei unzureichendem Kontrast ersetzt, keine Inhaltsänderung. Bei der Live-Eingabe zusätzlich NOVAs spezifische dunkle Suchschrift korrigiert.

C4: Neun unterschiedliche Live-Dateien einschließlich Template-Bündel und Prüfsummen. Vor jeder Stufe Originale privat gesichert und Hash-Grundlinie geprüft. Erste Kompilierprüfung wegen OPC-Registrierungsreihenfolge zurückgerollt; danach korrekt registriert und erfolgreich. Eine leere Änderungsstufe blieb ohne Auslieferung. Endabgleich von Bündel/Template/lokaler Quelle und anonyme Wartungsgrenze separat protokolliert.

C5: Echte 390/768/1280-Pixel-Matrix Hell/Dunkel, Textkontrast 8,01:1 bzw. 8,46:1. Kategorie und Produkt zusätzlich 1280/390 in beiden Modi. Kein Überlauf/Bildfehler in den aufgezeichneten Ansichten. Konto-/Warenkorb-Menüs, Eingabe in die Suche und sichtbarer Fokus geprüft. Browser-Größenwechsel benötigten Neuladen; die erste Matrix hatte unverändert 1280 Pixel und wurde ausdrücklich nicht als responsive Abnahme verwendet. Die korrigierte Matrix prüft tatsächliche innerWidth-Werte. Kein vollständiges Axe-/Screenreader- oder Core-Web-Vitals-Audit behauptet.

C6 abgeschlossen am 09.11.2026: Produktcommit 0e3c476 auf GitHub-main veröffentlicht. Zusätzliche vorhandene MGD-Suche-/Katalogarbeit beim Rebase erhalten, Changelog beider Arbeiten zusammengeführt, vier Live-Produktdateien nach Rebase hashgleich. README, Changelog, Prüfbericht und zwei vorhandene AI_Knowledge-Notizen aktualisiert. Keine neue Version oder Release-ZIP veröffentlicht. Die bestehenden öffentlichen 0.6.0-Pakete bleiben unverändert; Testshop und main enthalten den UI-Patch.
