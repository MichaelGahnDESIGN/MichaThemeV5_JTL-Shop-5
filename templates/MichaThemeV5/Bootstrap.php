<?php

declare(strict_types=1);

namespace Template\MichaThemeV5;

use JTL\Shop;
use JTL\Template\Bootstrapper;

require_once __DIR__ . '/php/LicenseClient.php';

/**
 * Stellt dem Template die Variable $mtPro bereit, z. B. {if $mtPro['custom-code']}.
 * Ist dieser Einstiegspunkt nicht verfügbar oder schlägt er fehl, fehlt die Variable und alle Pro-Funktionen bleiben aus.
 * HINWEIS: Pfad und Basisklasse sind noch nicht in einem laufenden JTL-Shop 5 verifiziert.
 */
class Bootstrap extends Bootstrapper
{
    private const MODULES = ['custom-code'];

    public function boot(): void
    {
        parent::boot();
        $pro = [];
        try {
            $token = (string) ($this->getConfig()->getValue('mt_license_token') ?? '');
            $client = new \MichaThemeV5\Client\LicenseClient(
                getenv('MT_LICENSE_API') ?: 'https://theme.michael-gahn.de/api',
                (string) parse_url(Shop::getURL(), PHP_URL_HOST),
                static function (string $url): ?array {
                    $ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
                    $body = @file_get_contents($url, false, $ctx);
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
                static fn (string $k): ?array => Shop::Container()->getCache()->get($k) ?: null,
                static function (string $k, array $v, int $ttl): void {
                    Shop::Container()->getCache()->set($k, $v, ['mt_license'], $ttl);
                }
            );
            foreach (self::MODULES as $m) {
                $pro[$m] = $client->allows($token, $m);
            }
        } catch (\Throwable) {
            $pro = [];
        }
        Shop::Smarty()->assign('mtPro', $pro);
    }
}
