<?php

declare(strict_types=1);

namespace JTL\DB {
    interface DbInterface {}
}

namespace JTL {
    final class Shop
    {
        public static function getURL(): string { return 'https://shop.example.invalid'; }
        public static function Container(): object
        {
            return new class {
                public function getCache(): object
                {
                    return new class {
                        public function get(string $key): mixed { return null; }
                        public function set(string $key, mixed $value, array $tags, int $ttl): void {}
                    };
                }
            };
        }
    }
}

namespace {
    // Echte Lizenzklasse, JTL und Netzwerk bleiben im Test vollständig ersetzt.
    define('PFAD_ROOT', dirname(__DIR__));
    require dirname(__DIR__) . '/plugins/MichaThemeV5/src/Generated/LicenseClient.php';
    require dirname(__DIR__) . '/plugins/MichaThemeV5/src/License/LicenseService.php';
    $calls = [];
    $http = static function (string $url, array $request) use (&$calls): array {
        $calls[] = [$url, $request];
        return ['status' => 200, 'json' => ['valid' => true, 'license' => ['tier' => 'pro', 'modules' => []], 'cache_valid_until' => gmdate('c', time() + 3600)]];
    };
    $service = new \Plugin\MichaThemeV5\License\LicenseService(new class implements \JTL\DB\DbInterface {}, $http);
    if (!$service->client()->state('test.license.token')['valid']) {
        throw new \RuntimeException('Gültige Testantwort wurde nicht erkannt.');
    }
    [$url, $request] = $calls[0];
    if ($url !== 'https://theme.michael-gahn.de/api/license/verify' || parse_url($url, PHP_URL_QUERY) !== null
        || $request['method'] !== 'POST' || $request['headers']['Authorization'] !== 'Bearer test.license.token'
        || json_decode($request['body'], true) !== ['domain' => 'shop.example.invalid']) {
        throw new \RuntimeException('Unsicherer Lizenztransport.');
    }
    echo "JTL-Lizenzdienst: POST, Bearer-Header und Domainbindung im Request geprüft.\n";
}
