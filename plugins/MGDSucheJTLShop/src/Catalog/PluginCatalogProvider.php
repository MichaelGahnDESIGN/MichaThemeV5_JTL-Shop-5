<?php

declare(strict_types=1);

namespace Plugin\MGDSucheJTLShop\Catalog;

use JTL\Plugin\Helper;

/**
 * Liest den zentral verwalteten MGD-Plugin-Katalog für JTL-Shop 5.
 *
 * Die Darstellung, Texte, Kategorien, Bilder, Versionen und Download-Links
 * stammen ausschließlich aus dem öffentlichen Katalog von theme.michael-gahn.de.
 * Dieses Plugin überträgt dabei keine Shop-, Konto- oder Besucherdaten. Die
 * kleine lokale Liste ist nur eine Offline-Ausfallsicherung.
 */
final class PluginCatalogProvider
{
    private const CATALOG_URL = 'https://theme.michael-gahn.de/api/catalog/plugins.json';
    // eigene Katalog-ID, alt + neu (Plattform nutzt plugin-suche-jtl)
    private const SELF_IDS = ['plugin-suche-jtl', 'plugin-mgd-suche-jtl'];
    private const MAX_BYTES = 524288;

    /** @var list<array<string, mixed>> */
    private const FALLBACK = [
        [
            'id' => 'MGDSucheJTLShop',
            'name' => 'MGD Suche für JTL-Shop',
            'description' => 'Merkmal-Hinweise und direkte native Filter für die JTL-Shop-Suche.',
            'platform' => 'jtl',
            'status' => 'live',
            'tier' => 'free',
            'version' => '0.3.0',
            'detailsUrl' => 'https://theme.michael-gahn.de',
            'downloadUrl' => 'https://theme.michael-gahn.de',
            'category' => 'Administration',
            'accent' => '#a8101a',
        ],
    ];

    private string $catalogSource = 'lokaler Ausfallschutz';

    /**
     * @return list<array{id: string, name: string, description: string, category: string, version: string, status: string, tier: string, details_url: ?string, download_url: ?string, image_url: ?string, image_alt: string, accent: ?string, features: list<string>, recommended: bool, installed: bool}>
     */
    public function recommendations(): array
    {
        $items = $this->remoteItems() ?? $this->normalizeFeed(['schemaVersion' => 1, 'items' => self::FALLBACK]) ?? [];
        foreach ($items as $index => $item) {
            $items[$index]['installed'] = $this->isInstalled($item['id']);
        }

        return $items;
    }

    public function source(): string
    {
        return $this->catalogSource;
    }

    /** @return list<array<string, mixed>>|null */
    private function remoteItems(): ?array
    {
        $payload = $this->downloadCatalog();
        if ($payload === null) {
            return null;
        }
        $feed = json_decode($payload, true);
        if (!is_array($feed)) {
            return null;
        }
        $items = $this->normalizeFeed($feed);
        if ($items === null) {
            return null;
        }

        $this->catalogSource = 'theme.michael-gahn.de';

        return $items;
    }

    /** Der Endpunkt ist bewusst fest verdrahtet: HTTPS, keine Weiterleitungen, Größenlimit. */
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
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_MAXFILESIZE => self::MAX_BYTES,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'MGD-Suche-JTL-Shop/0.3',
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        // Seit PHP 8 wird der cURL-Handle automatisch freigegeben; ein
        // explizites curl_close() erzeugt ab PHP 8.5 nur noch eine Warnung.
        unset($curl);

        return is_string($body) && strlen($body) <= self::MAX_BYTES && $status === 200 ? $body : null;
    }

    /**
     * Liest ausschließlich Schema-Version 1. Damit entscheidet der zentrale
     * Katalog, welche Plugins sichtbar sind; unbekannte Felder werden verworfen.
     *
     * @param array<string, mixed> $feed
     * @return list<array<string, mixed>>|null
     */
    private function normalizeFeed(array $feed): ?array
    {
        if (($feed['schema'] ?? null) === 1 && ($feed['platform'] ?? null) === 'jtl' && is_array($feed['items'] ?? null)) {
            return $this->normalizeRecommendations($feed);
        }
        if (($feed['schemaVersion'] ?? null) !== 1 || !is_array($feed['items'] ?? null)) {
            return null;
        }
        $related = [];
        foreach ($feed['items'] as $entry) {
            if (is_array($entry) && in_array($entry['id'] ?? null, self::SELF_IDS, true) && is_array($entry['related'] ?? null)) {
                $related = array_values(array_filter($entry['related'], 'is_string'));
                break;
            }
        }

        $items = [];
        foreach (array_slice(array_values($feed['items']), 0, 80) as $position => $entry) {
            if (!is_array($entry) || in_array($entry['id'] ?? null, self::SELF_IDS, true) || !$this->supportsJTL($entry)) {
                continue;
            }
            $item = $this->normalizeItem($entry, $related, $position);
            if ($item !== null) {
                $items[] = $item;
            }
        }
        usort($items, static fn (array $left, array $right): int => [-$left['recommended'], $left['status'] === 'live' ? 0 : 1, $left['position']] <=> [-$right['recommended'], $right['status'] === 'live' ? 0 : 1, $right['position']]);
        foreach ($items as $index => $item) {
            unset($items[$index]['position']);
        }

        return $items;
    }

    /** Der neue Feed enthält absichtlich nur sichere Anzeige-Metadaten. */
    private function normalizeRecommendations(array $feed): ?array
    {
        $details = $this->mgdUrl($feed['catalog_url'] ?? null);
        if ($details === null || $details !== 'https://theme.michael-gahn.de/downloads?plattform=jtl') {
            return null;
        }
        $items = [];
        foreach (array_slice(array_values($feed['items']), 0, 80) as $position => $entry) {
            if (!is_array($entry) || ($entry['platform'] ?? null) !== 'jtl') {
                continue;
            }
            $id = $entry['slug'] ?? null;
            $name = $this->plain($entry['name'] ?? '', 120);
            if (!is_string($id) || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $id) !== 1 || $name === '') {
                continue;
            }
            $items[] = [
                'id' => $id,
                'name' => $name,
                'description' => $this->plain($entry['description'] ?? '', 500),
                'category' => 'MGD Plugin',
                'version' => ltrim($this->plain($entry['version'] ?? '', 30), 'vV'),
                'status' => 'live',
                'tier' => 'free',
                'details_url' => $details,
                'download_url' => null,
                'image_url' => null,
                'image_alt' => '',
                'accent' => null,
                'features' => [],
                'recommended' => false,
                'position' => $position,
            ];
        }

        return $items;
    }

    /** @param array<string, mixed> $entry */
    private function supportsJTL(array $entry): bool
    {
        $platform = $entry['platform'] ?? $entry['platforms'] ?? [];
        if (is_string($platform)) {
            $platform = [$platform];
        }

        return is_array($platform) && count(array_intersect(['jtl', 'jtl5', 'all', 'other'], array_map('strtolower', array_filter($platform, 'is_string')))) > 0;
    }

    /**
     * @param array<string, mixed> $entry
     * @param list<string> $related
     * @return array<string, mixed>|null
     */
    private function normalizeItem(array $entry, array $related, int $position): ?array
    {
        $id = (string) ($entry['plugin_id'] ?? $entry['pluginId'] ?? $entry['id'] ?? '');
        $name = $this->plain($entry['name'] ?? $entry['title'] ?? '', 120);
        $description = $this->plain($entry['description'] ?? $entry['summary'] ?? '', 500);
        $status = $entry['status'] ?? 'live';
        $tier = $entry['tier'] ?? 'free';
        if (preg_match('/^[A-Za-z0-9_-]{2,100}$/', $id) !== 1 || $name === '' || !in_array($status, ['live', 'announced'], true)) {
            return null;
        }

        $features = [];
        foreach (array_slice(is_array($entry['features'] ?? null) ? $entry['features'] : [], 0, 3) as $feature) {
            $feature = $this->plain($feature, 100);
            if ($feature !== '') {
                $features[] = $feature;
            }
        }

        return [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'category' => $this->plain($entry['category'] ?? 'MGD Plugin', 40) ?: 'MGD Plugin',
            'version' => ltrim($this->plain($entry['version'] ?? '', 30), 'vV'),
            'status' => $status,
            'tier' => $tier === 'paid' ? 'paid' : 'free',
            'details_url' => $this->mgdUrl($entry['detailsUrl'] ?? $entry['url'] ?? null),
            'download_url' => $status === 'live' && $tier !== 'paid' ? $this->mgdUrl($entry['downloadUrl'] ?? null) : null,
            'image_url' => $this->mgdUrl($entry['imageUrl'] ?? null),
            'image_alt' => $this->plain($entry['imageAlt'] ?? '', 160),
            'accent' => is_string($entry['accent'] ?? null) && preg_match('/^#[0-9a-fA-F]{6}$/', $entry['accent']) ? strtolower($entry['accent']) : null,
            'features' => $features,
            'recommended' => in_array($id, $related, true),
            'position' => $position,
        ];
    }

    /** Akzeptiert nur HTTPS-Adressen des zentralen MGD-Hosts. */
    private function mgdUrl(mixed $url): ?string
    {
        if (!is_string($url) || strlen($url) > 500 || preg_match('/[\x00-\x20\x7f<>"\'\\\\]/', $url) === 1) {
            return null;
        }
        $parts = parse_url($url);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || strtolower((string) ($parts['host'] ?? '')) !== 'theme.michael-gahn.de' || isset($parts['user'], $parts['pass'], $parts['port'])) {
            return null;
        }

        return $url;
    }

    private function plain(mixed $value, int $max): string
    {
        if (!is_string($value)) {
            return '';
        }

        return trim(mb_substr((string) preg_replace('/[\x00-\x1f\x7f]/', ' ', strip_tags($value)), 0, $max));
    }

    private function isInstalled(string $pluginID): bool
    {
        return class_exists(Helper::class) && Helper::getIDByPluginID($pluginID) > 0;
    }
}
