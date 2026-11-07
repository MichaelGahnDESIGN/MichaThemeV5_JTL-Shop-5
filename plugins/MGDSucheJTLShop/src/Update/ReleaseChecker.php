<?php

declare(strict_types=1);

namespace Plugin\MGDSucheJTLShop\Update;

/**
 * Prüft ausschließlich im JTL-Backend auf veröffentlichte, stabile Releases.
 *
 * Das Muster entspricht dem bestehenden MGD-JTL-Release-Prüfer: Die URL ist
 * fest eingebaut, TLS und Antwortgröße sind begrenzt, und ein Download-Link
 * wird nur aus einem geprüften Tag und einem erwarteten Release-Asset gebaut.
 * Dieser Dienst lädt oder installiert niemals Plugin-Dateien selbst.
 */
final class ReleaseChecker
{
    private const ENDPOINT = 'https://api.github.com/repos/MichaelGahnDESIGN/MichaThemeV5_JTL-Shop-5/releases?per_page=50';
    private const RELEASE_PREFIX = 'https://github.com/MichaelGahnDESIGN/MichaThemeV5_JTL-Shop-5/releases/tag/';
    private const DOWNLOAD_PREFIX = 'https://github.com/MichaelGahnDESIGN/MichaThemeV5_JTL-Shop-5/releases/download/';
    private const ASSET_PREFIX = 'MGD-Suche-JTL-Shop-Plugin-';
    private const TAG_PREFIX = 'mgd-suche-v';
    private const CACHE_SECONDS = 3600;
    private const MAX_BYTES = 131072;
    private const VERSION_PATTERN = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/D';
    private const TAG_PATTERN = '/^mgd-suche-v(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/D';

    /**
     * @return array{ok: bool, update: bool, current: string, latest: ?string, release_url: ?string, download_url: ?string}
     */
    public function check(string $currentVersion, bool $enabled): array
    {
        $empty = $this->emptyResult($currentVersion);
        if ($enabled === false || preg_match(self::VERSION_PATTERN, $currentVersion) !== 1) {
            return $empty;
        }

        $cache = $this->cachePath();
        $stored = $this->readCache($cache);
        if ($stored !== null && $stored['checked_at'] <= time() && time() - $stored['checked_at'] < self::CACHE_SECONDS) {
            return $this->result($currentVersion, $stored['release']);
        }

        $release = $this->fetch();
        $this->saveCache($cache, ['checked_at' => time(), 'release' => $release]);

        return $release === null ? $empty : $this->result($currentVersion, $release);
    }

    /**
     * Reine Parsergrenze für Tests: Akzeptiert nur das erwartete Asset dieses
     * Plugins. Eine beliebige URL aus der API wird nie an den Admin ausgegeben.
     *
     * @param array<string, mixed> $data
     * @return array{tag: string, release_url: string, download_url: string}|null
     */
    public static function parseRelease(array $data): ?array
    {
        $tag = $data['tag_name'] ?? null;
        if (
            ($data['draft'] ?? true) !== false
            || ($data['prerelease'] ?? true) !== false
            || !is_string($tag)
            || preg_match(self::TAG_PATTERN, $tag) !== 1
            || !is_array($data['assets'] ?? null)
        ) {
            return null;
        }

        $version = substr($tag, strlen(self::TAG_PREFIX));
        $name = self::ASSET_PREFIX . $version . '.zip';
        $downloadURL = self::DOWNLOAD_PREFIX . $tag . '/' . $name;
        foreach ($data['assets'] as $asset) {
            if (
                is_array($asset)
                && ($asset['name'] ?? null) === $name
                && ($asset['browser_download_url'] ?? null) === $downloadURL
            ) {
                return [
                    'tag' => $tag,
                    'release_url' => self::RELEASE_PREFIX . $tag,
                    'download_url' => $downloadURL,
                ];
            }
        }

        return null;
    }

    /**
     * Ein Repository kann mehrere Plugin-Familien enthalten. Deshalb wird aus
     * der Release-Liste nur der höchste gültige Tag dieser Plugin-Familie
     * ausgewählt; Theme- und andere Plugin-Releases bleiben unsichtbar.
     *
     * @param list<mixed> $releases
     * @return array{tag: string, release_url: string, download_url: string}|null
     */
    public static function newestMatchingRelease(array $releases): ?array
    {
        $newest = null;
        foreach ($releases as $releaseData) {
            if (is_array($releaseData) === false) {
                continue;
            }
            $release = self::parseRelease($releaseData);
            if (
                $release !== null
                && ($newest === null || version_compare(
                    substr($release['tag'], strlen(self::TAG_PREFIX)),
                    substr($newest['tag'], strlen(self::TAG_PREFIX)),
                    '>'
                ))
            ) {
                $newest = $release;
            }
        }

        return $newest;
    }

    /** @return array{tag: string, release_url: string, download_url: string}|null */
    private function fetch(): ?array
    {
        if (function_exists('curl_init') === false) {
            return null;
        }
        $curl = curl_init(self::ENDPOINT);
        if ($curl === false) {
            return null;
        }

        $body = '';
        curl_setopt_array($curl, [
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json', 'User-Agent: MGD-Suche-JTL-Shop/0.2.0'],
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int {
                unset($handle);
                if (strlen($body) + strlen($chunk) > self::MAX_BYTES) {
                    return 0;
                }
                $body .= $chunk;

                return strlen($chunk);
            },
        ]);
        $ok = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($ok === false || $status !== 200) {
            return null;
        }

        try {
            $data = json_decode($body, true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($data) && array_is_list($data) ? self::newestMatchingRelease($data) : null;
    }

    /**
     * @param array{tag: string, release_url: string, download_url: string}|null $release
     * @return array{ok: bool, update: bool, current: string, latest: ?string, release_url: ?string, download_url: ?string}
     */
    private function result(string $current, ?array $release): array
    {
        if ($release === null || preg_match(self::TAG_PATTERN, $release['tag']) !== 1) {
            return $this->emptyResult($current);
        }
        $tag = $release['tag'];
        $version = substr($tag, strlen(self::TAG_PREFIX));
        $expectedAsset = self::ASSET_PREFIX . $version . '.zip';
        if (
            $release['release_url'] !== self::RELEASE_PREFIX . $tag
            || $release['download_url'] !== self::DOWNLOAD_PREFIX . $tag . '/' . $expectedAsset
        ) {
            return $this->emptyResult($current);
        }

        return [
            'ok' => true,
            'update' => version_compare($version, $current, '>'),
            'current' => $current,
            'latest' => $version,
            'release_url' => $release['release_url'],
            'download_url' => $release['download_url'],
        ];
    }

    /** @return array{ok: false, update: false, current: string, latest: null, release_url: null, download_url: null} */
    private function emptyResult(string $current): array
    {
        return ['ok' => false, 'update' => false, 'current' => $current, 'latest' => null, 'release_url' => null, 'download_url' => null];
    }

    private function cachePath(): string
    {
        // Der Hash trennt mehrere Shop-Installationen auf demselben Server.
        $root = defined('PFAD_ROOT') ? (string) PFAD_ROOT : __DIR__;

        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . 'mgd-search-jtl-update-' . hash('sha256', $root) . '.json';
    }

    /** @return array{checked_at: int, release: array{tag: string, release_url: string, download_url: string}|null}|null */
    private function readCache(string $file): ?array
    {
        if (is_link($file) || !is_file($file)) {
            return null;
        }
        $raw = @file_get_contents($file, false, null, 0, self::MAX_BYTES + 1);
        if (is_string($raw) && strlen($raw) > self::MAX_BYTES) {
            return null;
        }
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data) || !is_int($data['checked_at'] ?? null)) {
            return null;
        }
        $release = $data['release'] ?? null;
        if ($release !== null && (!is_array($release) || self::parseRelease([
            'tag_name' => $release['tag'] ?? null,
            'draft' => false,
            'prerelease' => false,
            'assets' => [[
                'name' => self::ASSET_PREFIX . substr((string) ($release['tag'] ?? ''), strlen(self::TAG_PREFIX)) . '.zip',
                'browser_download_url' => $release['download_url'] ?? null,
            ]],
        ]) === null)) {
            return null;
        }

        return ['checked_at' => $data['checked_at'], 'release' => $release];
    }

    /** @param array{checked_at: int, release: array{tag: string, release_url: string, download_url: string}|null} $data */
    private function saveCache(string $file, array $data): void
    {
        if (is_link($file)) {
            return;
        }
        $temporary = @tempnam(sys_get_temp_dir(), 'mgd-search-update-');
        if (!is_string($temporary)) {
            return;
        }
        @chmod($temporary, 0600);
        $json = json_encode($data, JSON_UNESCAPED_SLASHES);
        $written = is_string($json) ? @file_put_contents($temporary, $json, LOCK_EX) : false;
        if ($written === false || !@rename($temporary, $file)) {
            @unlink($temporary);
        }
    }
}
