# Hell-/Dunkelprüfung im JTL-Testshop

Stand: 08.11.2026. JTL-Shop 5.8.2, installiertes Theme/Begleitplugin 0.6.0 mit gezieltem, noch unveröffentlichtem UI-Patch. Die öffentlichen 0.6.0-Pakete wurden nicht ersetzt.

## Korrekturen

Die NOVA-Scrollpfeile hatten einen fest weißen Verlauf. Sie verwenden jetzt Theme-Variablen, 44 Pixel Höhe, verständliche Namen und Tastaturbedienung mit Enter/Leertaste. Versteckte, deaktivierte Pfeile werden aus der Tab-Reihenfolge genommen. Verschwindet der fokussierte Pfeil am Ende, erhält der gegenüberliegende Pfeil den Fokus.

Eingefügte Word-Texte enthielten color:#767171. Das neue lokale Modul mt-content-contrast.js prüft nur explizite, nahezu neutrale Textfarben innerhalb des Inhalts. Bei weniger als 4,5:1 wird eine ausreichend kontrastierende Theme-Textfarbe verwendet. Absichtlich farbige Texte, Bedienelemente, bearbeitbare Inhalte und Bild-/Parallaxbereiche bleiben ausgenommen. Der Prüfer ändert ausschließlich Klassen im Browser, keine gespeicherten Texte oder Einstellungen, und reagiert auf Farbmoduswechsel und nachgeladene Inhalte. Es gibt keine neue Abhängigkeit, Speicherung oder Netzwerkanfrage.

Typeahead hatte beim Farbwechsel seine alte Hintergrundfarbe behalten; die eingegebene Suchschrift wurde außerdem von einer spezifischeren NOVA-Regel dunkel gehalten. Beide Flächen/Textzustände sind jetzt an Theme-Variablen gebunden. Textunterstreichungen sind entfernt; Links in Absätzen erhalten zusätzlich Schriftgewicht, Tastaturfokus bleibt sichtbar.

## Tatsächlich geprüft

| Ansicht | Hell | Dunkel |
| --- | --- | --- |
| Startseite/Textpassage, 1280 Pixel | geprüft, Text 8,01:1 | geprüft, Text 8,46:1 |
| Startseite/Textpassage, 768 Pixel | geprüft, Text 8,01:1 | geprüft, Text 8,46:1 |
| Startseite/Textpassage, 390 Pixel | geprüft, Text 8,01:1 | geprüft, Text 8,46:1 |
| Kategorie Sandalen, 1280/390 Pixel | geprüft | geprüft |
| Produktansicht, 1280/390 Pixel | geprüft | geprüft |
| Konto- und leerer Warenkorb-Dropdown | helle Fläche/dunkle Schrift | dunkle Fläche/helle Schrift |
| Suchfeld während tatsächlicher Eingabe | helle Fläche/dunkle Schrift, Fokus sichtbar | dunkle Fläche/helle Schrift, Fokus sichtbar |

Kein horizontaler Überlauf in den genannten Ansichten, keine defekten sichtbaren Bilder bei den aufgezeichneten Prüfungen. Enter verschiebt die native Navigation tatsächlich; der aktive Pfeil hat einen sichtbaren 2-Pixel-Fokus. Leertaste wurde ebenfalls ausgelöst. Keine JavaScript-Fehler bei den abschließenden geprüften Seiten. Die Testanmeldung lief regulär über die vorhandenen Zugangsdaten; keine Kennwortkopie oder Cookieausgabe. Die bestehende Einstellung „Wie das Gerät“ wurde wiederhergestellt. Die geschützte Vorschau bleibt für Michael geöffnet.

Lokal bestanden sieben Regressionstests für Referenzkontrast, die konkrete Word-Farbe, beide Farbmodi, Akzent-/Alpha-Erhalt, ungeeignete eigene Farben und Browserfarbwerte. Syntaxprüfungen beider geänderter/neuer Skripte bestanden. Wiederholbar: node --test tests/content-contrast.test.cjs.

## Bereitstellung und Rückfall

Vier gezielt ausgewählte Theme-Dateien wurden nach Sicherung übertragen: mt-jtl.css, mt-jtl.js, mt-content-contrast.js und layout/header.tpl. Dieselben Dateien liegen im mitgelieferten Template-Bündel; dessen Prüfsummen wurden aktualisiert. Neun unterschiedliche Serverdateien sind damit betroffen. Der Header wurde mit JTLs tatsächlicher Smarty-/OPC-Registrierung kompiliert. Die 85 Bündeldateien werden gegen das installierte Template und die aktuelle lokale Quelle abgeglichen. Versionskennungen aus SHA-256 vermeiden veraltete CSS-/JS-Antworten.

Die Originale und Manifeste liegen im privaten serverseitigen Sicherungsbereich außerhalb des öffentlichen Webverzeichnisses. Ein anfänglicher Prüfaufbau registrierte OPC vor dem eigenen Smarty-Objekt; der Rückfall erhielt den Originalstand. Mit korrekter Reihenfolge bestand die Kompilierung. Nachgeladene NOVA-Pfeile werden erst beim Einfügen initialisiert. Eine leere Änderungsstufe wurde nicht ausgerollt. Diese Prüfablaufkorrekturen ändern keine Produktanforderung.

Rückfall: Zuerst aktuelle Hashes mit dem letzten Auslieferungsmanifest vergleichen. Danach ausschließlich die sieben ursprünglichen Dateien aus der ersten vollständigen Sicherung wiederherstellen und die zwei neu eingeführten Modulkopien entfernen, sofern sie noch den ausgelieferten Hash haben. Header nativ neu kompilieren. Keine pauschale Shop- oder Datenbankwiederherstellung. Keine Bestellungen, Kunden- oder OPC-Inhalte wurden verändert. Der Wartungsmodus bleibt aktiv; anonyme Besucher erhalten weiterhin HTTP 503.

## Grenzen

Die Kontrastwerte gelten für die konkret geprüfte Text-/Hintergrundkombination. Das ist keine vollständige WCAG-, Screenreader-, Bildkontrast- oder Leistungszertifizierung. Beliebige eigene Farben und beliebige Fotos können eine erneute Prüfung benötigen. Es wurden keine Zahlungs-/Bestellvorgänge ausgelöst. Die Plattform bleibt privat; Rechts- und öffentliche Startfreigabe sind eigenständige Schritte.
