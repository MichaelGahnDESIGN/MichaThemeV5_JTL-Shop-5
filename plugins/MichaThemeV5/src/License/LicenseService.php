<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\License;

use JTL\DB\DbInterface;
use JTL\Shop;
use MichaThemeV5\Plugin\{LicenseClient, LicenseTexts, Sanitizer};

defined('PFAD_ROOT') || exit;

/**
 * Lizenzcode und Status. Der Code bleibt dort, wo ihn das Template schon liest (ttemplateeinstellungen,
 * Abschnitt mt_license, Name mt_license_token): keine Migration. Er steht nie in Logs, Fehlertexten oder im Frontend.
 */
final class LicenseService
{
    private const SECTION = 'mt_license';
    private const NAME = 'mt_license_token';
    private const TEMPLATE = 'MichaThemeV5';
    private const TAG = 'mt_license';

    /** @param (callable(string):?array)|null $http nur für Tests: ['status' => int, 'json' => array] */
    public function __construct(private readonly DbInterface $db, private $http = null)
    {
    }

    public static function forShop(): self
    {
        return new self(Shop::Container()->getDB());
    }

    public function token(): string
    {
        $row = $this->db->select('ttemplateeinstellungen', 'cTemplate', self::TEMPLATE, 'cSektion', self::SECTION, 'cName', self::NAME);
        $t = $row !== null && isset($row->cWert) ? trim((string) $row->cWert) : '';

        return $t;
    }

    public function hasToken(): bool
    {
        return $this->token() !== '';
    }

    /** Letzte 6 Zeichen, der Rest bleibt verborgen. */
    public function masked(): string
    {
        $t = $this->token();

        return $t === '' ? '' : '…' . substr($t, -6);
    }

    public function save(string $token): bool
    {
        $token = trim($token);
        if (!Sanitizer::licenseFormat($token)) {
            return false;
        }
        $this->write($token);
        $this->client()->forget($token);

        return true;
    }

    public function remove(): void
    {
        $this->write('');
    }

    private function write(string $value): void
    {
        (new \JTL\Template\Config(self::TEMPLATE, $this->db))->updateConfigInDB(self::SECTION, self::NAME, $value);
        try {
            Shop::Container()->getCache()->flushTags([\CACHING_GROUP_OPTION, \CACHING_GROUP_TEMPLATE]);
        } catch (\Throwable) {
            // egal
        }
    }

    public function client(): LicenseClient
    {
        $cache = Shop::Container()->getCache();
        $api = getenv('MT_LICENSE_API') ?: 'https://theme.michael-gahn.de/api';

        return new LicenseClient(
            $api,
            (string) parse_url(Shop::getURL(), PHP_URL_HOST),
            $this->http ?? static function (string $url): ?array {
                $ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true, 'follow_location' => 0, 'max_redirects' => 0]]);
                $body = @file_get_contents($url, false, $ctx, 0, 262144);
                if ($body === false) {
                    return null;
                }
                $status = 0;
                foreach ($http_response_header ?? [] as $h) {
                    if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
                        $status = (int) $m[1];
                    }
                }
                $json = json_decode($body, true);

                return ['status' => $status, 'json' => is_array($json) ? $json : []];
            },
            static fn (string $k): ?array => $cache->get($k) ?: null,
            static function (string $k, array $v, int $ttl) use ($cache): void {
                $cache->set($k, $v, [self::TAG], $ttl);
            }
        );
    }

    /** @return array<string,mixed> Der Shop bricht nie: bei jedem Fehler gilt Free. */
    public function state(): array
    {
        try {
            return $this->client()->state($this->token());
        } catch (\Throwable) {
            return ['valid' => false, 'tier' => 'free', 'modules' => [], 'source' => 'none', 'reason' => 'unreachable'];
        }
    }

    public function allows(string $module): bool
    {
        try {
            return $this->client()->allows($this->token(), $module);
        } catch (\Throwable) {
            return false;
        }
    }

    /** "Jetzt prüfen": höchstens einmal pro Minute wirklich neu fragen. */
    public function recheck(\Plugin\MichaThemeV5\Support\FileKv $kv): bool
    {
        if ($kv->get('recheck') !== null) {
            return false;
        }
        $kv->set('recheck', 1, 60);
        $this->client()->forget($this->token());

        return true;
    }

    /** @return array{kind:string,title:string,text:string} */
    public function describe(): array
    {
        $s = $this->state();

        return LicenseTexts::describe($s, is_string($s['period_end'] ?? null) ? $s['period_end'] : null);
    }
}
