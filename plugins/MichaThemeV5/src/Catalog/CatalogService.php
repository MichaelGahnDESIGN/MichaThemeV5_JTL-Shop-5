<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Catalog;

use MichaThemeV5\Plugin\CatalogFeed;
use Plugin\MichaThemeV5\Support\FileKv;

defined('PFAD_ROOT') || exit;

/** Plugin-Empfehlungen: Feed von theme.michael-gahn.de. Fehler sind leise. Im Shop (Frontend) wird nie abgerufen. */
final class CatalogService
{
    public const SELF_ID = 'plugin-michatheme-v5-jtl';

    /** @param (callable(string):?array)|null $http nur für Tests: ['status' => int, 'body' => string] */
    public function __construct(private readonly FileKv $kv, private $http = null)
    {
    }

    /** @return array{items:list<array<string,mixed>>,stale:bool,error:?string,checked_at:int} */
    public function load(bool $refresh = false): array
    {
        $none = ['items' => [], 'stale' => false, 'error' => 'unreachable', 'checked_at' => 0];
        try {
            $feed = new CatalogFeed(
                'jtl',
                self::SELF_ID,
                $this->http ?? static function (string $url): ?array {
                    $ctx = stream_context_create(['http' => [
                        'timeout' => 5, 'ignore_errors' => true, 'follow_location' => 0, 'max_redirects' => 0, 'header' => "Accept: application/json\r\n",
                    ]]);
                    $h = @fopen($url, 'rb', false, $ctx);
                    if ($h === false) {
                        return null;
                    }
                    stream_set_timeout($h, 5);
                    $body = (string) stream_get_contents($h, CatalogFeed::MAX_BYTES + 1);
                    fclose($h);
                    $status = 0;
                    foreach ($http_response_header ?? [] as $line) {
                        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                            $status = (int) $m[1];
                        }
                    }

                    return ['status' => $status, 'body' => $body];
                },
                fn (string $k): mixed => $this->kv->get($k),
                fn (string $k, mixed $v, int $ttl) => $this->kv->set($k, $v, $ttl)
            );

            return $feed->load($refresh);
        } catch (\Throwable) {
            return $none;
        }
    }
}
