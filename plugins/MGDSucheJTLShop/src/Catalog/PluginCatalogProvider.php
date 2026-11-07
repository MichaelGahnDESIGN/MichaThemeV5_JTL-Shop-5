<?php

declare(strict_types=1);

namespace Plugin\MGDSucheJTLShop\Catalog;

use JTL\Plugin\Helper;

/**
 * Liefert Empfehlungen für weitere MGD-Plugins im JTL-Backend.
 *
 * Die zentrale JSON-Datei ist nur eine öffentliche Produktliste. Fällt sie
 * aus, funktioniert eine absichtlich kleine lokale Fallback-Liste weiterhin.
 * Es werden keine Shop-, Konto- oder Besucherdaten an den Katalog gesendet.
 */
final class PluginCatalogProvider
{
    private const CATALOG_URL = 'https://theme.michael-gahn.de/api/catalog/plugins.json';

    /** @var list<array{id: string, name: string, description: string, url: string}> */
    private const FALLBACK = [
        [
            'id' => 'MGDSucheJTLShop',
            'name' => 'MGD Suche',
            'description' => 'Merkmal-Hinweise und direkte native Filter für die JTL-Shop-Suche.',
            'url' => 'https://github.com/MichaelGahnDESIGN/MichaThemeV5_JTL-Shop-5/tree/main/plugins/MGDSucheJTLShop',
        ],
        [
            'id' => 'MichaThemeV5NotificationCenter',
            'name' => 'MichaThemeV5 Notification Center',
            'description' => 'Öffentliche und persönliche Benachrichtigungen für MichaThemeV5.',
            'url' => 'https://github.com/MichaelGahnDESIGN/MichaThemeV5_JTL-Shop-5/tree/main/plugins/MichaThemeV5NotificationCenter',
        ],
    ];

    private string $catalogSource = 'lokaler Fallback';

    /**
     * @return list<array{id: string, name: string, description: string, url: string, installed: bool}>
     */
    public function recommendations(): array
    {
        $items = $this->remoteItems() ?? self::FALLBACK;
        $result = [];
        foreach ($items as $item) {
            $result[] = $item + ['installed' => $this->isInstalled($item['id'])];
        }

        return $result;
    }

    public function source(): string
    {
        return $this->catalogSource;
    }

    /**
     * @return list<array{id: string, name: string, description: string, url: string}>|null
     */
    private function remoteItems(): ?array
    {
        $payload = $this->downloadCatalog();
        if ($payload === null) {
            return null;
        }
        $decoded = json_decode($payload, true);
        if (is_array($decoded) === false) {
            return null;
        }
        $entries = $decoded['plugins'] ?? $decoded;
        if (is_array($entries) === false) {
            return null;
        }

        $items = [];
        foreach ($entries as $entry) {
            if (is_array($entry) === false || $this->supportsJTL($entry) === false) {
                continue;
            }
            $item = $this->normalizeItem($entry);
            if ($item !== null) {
                $items[] = $item;
            }
        }
        if ($items === []) {
            return null;
        }

        $this->catalogSource = 'zentraler MGD-Katalog';

        return $items;
    }

    private function downloadCatalog(): ?string
    {
        if (function_exists('curl_init') === false) {
            return null;
        }
        $curl = curl_init(self::CATALOG_URL);
        if ($curl === false) {
            return null;
        }
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => 'MGD-Suche-JTL-Shop/0.1',
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        return is_string($body) && $status === 200 ? $body : null;
    }

    /** @param array<string, mixed> $entry */
    private function supportsJTL(array $entry): bool
    {
        $platforms = $entry['platforms'] ?? $entry['platform'] ?? [];
        if (is_string($platforms)) {
            $platforms = [$platforms];
        }

        return is_array($platforms) && in_array('jtl', array_map('strtolower', $platforms), true);
    }

    /**
     * @param array<string, mixed> $entry
     * @return array{id: string, name: string, description: string, url: string}|null
     */
    private function normalizeItem(array $entry): ?array
    {
        $id = (string) ($entry['plugin_id'] ?? $entry['pluginId'] ?? $entry['id'] ?? '');
        $name = trim((string) ($entry['name'] ?? $entry['title'] ?? ''));
        $description = trim((string) ($entry['description'] ?? $entry['summary'] ?? ''));
        $url = (string) ($entry['url'] ?? $entry['repository_url'] ?? '');
        $host = parse_url($url, PHP_URL_HOST);

        if (
            preg_match('/^[A-Za-z0-9_]{2,100}$/', $id) !== 1
            || $name === '' || mb_strlen($name) > 120
            || mb_strlen($description) > 350
            || filter_var($url, FILTER_VALIDATE_URL) === false
            || in_array($host, ['github.com', 'theme.michael-gahn.de'], true) === false
        ) {
            return null;
        }

        return ['id' => $id, 'name' => $name, 'description' => $description, 'url' => $url];
    }

    private function isInstalled(string $pluginID): bool
    {
        return class_exists(Helper::class) && Helper::getIDByPluginID($pluginID) > 0;
    }
}
