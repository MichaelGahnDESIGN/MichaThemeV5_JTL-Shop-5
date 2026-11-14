<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Catalog/PluginCatalogProvider.php';

use Plugin\MGDSucheJTLShop\Catalog\PluginCatalogProvider;

/** Prüft die zentrale Feed-Grenze ohne Netzwerk und ohne JTL-Installation. */
function catalogAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$provider = new PluginCatalogProvider();
$normalizer = new ReflectionMethod($provider, 'normalizeFeed');
$items = $normalizer->invoke($provider, [
    'schemaVersion' => 1,
    'items' => [
        ['id' => 'plugin-mgd-suche-jtl', 'platform' => 'jtl', 'related' => ['plugin-notification']],
        [
            'id' => 'plugin-notification', 'name' => 'Notification Center', 'description' => 'Datensparsame Benachrichtigungen.',
            'platform' => 'jtl', 'status' => 'live', 'tier' => 'free', 'version' => 'v0.2.0', 'category' => 'Kommunikation',
            'features' => ['Öffentliche Meldungen', 'Persönliche Meldungen'],
            'detailsUrl' => 'https://theme.michael-gahn.de/plugins/notification', 'downloadUrl' => 'https://theme.michael-gahn.de/downloads/notification.zip',
            'imageUrl' => 'https://theme.michael-gahn.de/uploads/catalog/0123456789abcdef0123456789abcdef.webp', 'accent' => '#5b4de3',
        ],
        [
            'id' => 'unsafe-plugin', 'name' => 'Unsicher', 'description' => 'Darf keinen fremden Link ausgeben.',
            'platform' => 'jtl', 'status' => 'live', 'detailsUrl' => 'https://example.invalid/details', 'downloadUrl' => 'https://example.invalid/download.zip',
        ],
    ],
]);

catalogAssert(is_array($items) && count($items) === 2, 'JTL-Feed wurde nicht korrekt gelesen.');
catalogAssert($items[0]['id'] === 'plugin-notification' && $items[0]['recommended'] === true, 'Empfehlungsreihenfolge oder related-Markierung fehlerhaft.');
catalogAssert($items[0]['download_url'] === 'https://theme.michael-gahn.de/downloads/notification.zip', 'Offizieller Download-Link wurde nicht akzeptiert.');
catalogAssert($items[0]['features'] === ['Öffentliche Meldungen', 'Persönliche Meldungen'], 'Katalog-Funktionsliste wurde nicht übernommen.');
catalogAssert($items[1]['details_url'] === null && $items[1]['download_url'] === null, 'Fremde Katalog-Links wurden nicht verworfen.');
catalogAssert($normalizer->invoke($provider, ['schemaVersion' => 2, 'items' => []]) === null, 'Unbekannte Schema-Version wurde akzeptiert.');

// Plattform-Adresse + eigene ID wie im echten Katalog
catalogAssert((new ReflectionClassConstant(PluginCatalogProvider::class, 'CATALOG_URL'))->getValue() === 'https://theme.michael-gahn.de/api/catalog/plugins.json', 'Katalog-Adresse passt nicht zur Plattform.');
$own = $normalizer->invoke($provider, ['schemaVersion' => 1, 'items' => [
    ['id' => 'plugin-suche-jtl', 'name' => 'MGD Suche', 'platform' => 'jtl', 'status' => 'live'],
    ['id' => 'plugin-notification', 'name' => 'Notification Center', 'platform' => 'jtl', 'status' => 'live'],
]]);
catalogAssert(is_array($own) && count($own) === 1 && $own[0]['id'] === 'plugin-notification', 'Eigener Katalog-Eintrag wird als Empfehlung angezeigt.');

echo "PluginCatalogProvider: OK\n";
