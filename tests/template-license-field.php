<?php

declare(strict_types=1);

// Lizenzfeld nicht in den JTL-Template-Einstellungen bearbeitbar (sonst Klartext + template_settings_log)
$xml = simplexml_load_file(dirname(__DIR__) . '/templates/MichaThemeV5/template.xml');
$hit = $xml->xpath('//Section[@Key="mt_license"]/Setting[@Key="mt_license_token"]');
if (count($hit) !== 1) {
    throw new RuntimeException('Lizenzfeld fehlt in template.xml (Plugin und Bootstrap lesen genau diese Zeile).');
}
if ((string) $hit[0]['Editable'] !== '0') {
    throw new RuntimeException('Lizenzfeld ist in den Template-Einstellungen bearbeitbar.');
}
echo "Template: Lizenzfeld nur über das Plugin, nicht in den Template-Einstellungen.\n";
