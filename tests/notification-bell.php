<?php

declare(strict_types=1);

// Glocke im Theme: ohne Plugin, altes Plugin (< 0.3.0), neues Plugin, Plugin wirft Fehler > nie weiße Seite
// Aufruf: php tests/notification-bell.php <fall>   (ohne Argument laufen alle Fälle in eigenen Prozessen)

namespace {
    $cases = ['ohne', 'alt', 'neu', 'kaputt'];
    $case = $argv[1] ?? null;
    if ($case === null) {
        $fails = 0;
        foreach ($cases as $c) {
            $out = [];
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . $c . ' 2>&1', $out, $code);
            $ok = $code === 0;
            echo ($ok ? 'OK   ' : 'FEHL ') . $c . ': ' . implode(' | ', $out) . "\n";
            $fails += $ok ? 0 : 1;
        }
        exit($fails === 0 ? 0 : 1);
    }
    define('PFAD_ROOT', __DIR__ . '/');
    $GLOBALS['mt_case'] = $case;
    $_SESSION['jtl_token'] = 'tok123';
}

namespace JTL {
    final class Shop
    {
        public static function getURL(): string { return 'https://shop.example.invalid'; }
        public static function getCustomer(): object { return (object) ['kKunde' => 7]; }
    }
}

namespace Template\NOVA {
    class Bootstrap
    {
        public array $assigned = [];
        public function boot(): void {}
        public function getSmarty(): object
        {
            $self = $this;
            return new class ($self) {
                public function __construct(private object $b) {}
                public function assign(string $k, mixed $v): void { $this->b->assigned[$k] = $v; }
            };
        }
        public function getCache(): object
        {
            return new class {
                public function get(string $k): mixed { return false; }
                public function set(string $k, mixed $v, array $t = [], int $ttl = 0): void {}
            };
        }
        public function getDB(): object
        {
            return new class {
                public function tableExists(string $t): bool { return true; }
                public function getSingleObject(string $q, array $p = []): ?object { return null; }
                public function selectAll(string $t, mixed ...$a): array { return []; }
                public function select(string $t, mixed ...$a): ?object { return null; }
                public function queryPrepared(string $q, array $p = [], int $r = 0): mixed { return []; }
                public function query(string $q, int $r = 0): mixed { return []; }
                public function insert(string $t, object $o): int { return 1; }
                public function upsert(string $t, object $o): int { return 1; }
            };
        }
    }
}

namespace Plugin\MichaThemeV5NotificationCenter {
    $c = $GLOBALS['mt_case'];
    $item = ['id' => 3, 'title' => 'Hallo </script><b>', 'body' => 'Text', 'target_url' => ''];
    if ($c === 'alt') {
        final class NotificationBridge
        {
            public static function forTemplate(object $db, int $customer): array { return ['public' => [['title' => 'Alt', 'body' => 'x', 'target_url' => '']], 'personal' => []]; }
        }
    } elseif ($c === 'neu') {
        final class NotificationBridge
        {
            public static function forTemplate(object $db, int $customer): array { return ['public' => [['id' => 3, 'title' => 'Hallo </script><b>', 'body' => 'Text', 'target_url' => '']], 'personal' => [], 'settings' => ['visible_limit' => 5]]; }
            public static function settings(): array { return ['visible_limit' => 5]; }
            public static function iconSvg(): string { return '<svg class="x"></svg>'; }
        }
    } elseif ($c === 'kaputt') {
        final class NotificationBridge
        {
            public static function forTemplate(object $db, int $customer): array { return ['public' => [['id' => 1, 'title' => 'x', 'body' => 'y', 'target_url' => '']], 'personal' => []]; }
            public static function settings(): array { throw new \RuntimeException('kaputt'); }
            public static function iconSvg(): string { throw new \RuntimeException('kaputt'); }
        }
    }
}

namespace {
    require dirname(__DIR__) . '/templates/MichaThemeV5/Bootstrap.php';
    $b = new \Template\MichaThemeV5\Bootstrap();
    try {
        $b->boot();
    } catch (\Throwable $e) {
        echo 'Absturz: ' . get_class($e) . ' ' . $e->getMessage();
        exit(1);
    }
    $a = $b->assigned;
    $json = (string) ($a['mtNotificationJson'] ?? '');
    $ok = match ($case) {
        'ohne'   => $a['mtNotificationsAvailable'] === false && $json === '' && $a['mtNotificationToken'] === '' && $a['mtNotificationIcon'] === '',
        'alt'    => $a['mtNotificationsAvailable'] === true && $json === '' && $a['mtNotificationToken'] === '' && count($a['mtNotifications']['public']) === 1,
        'neu'    => $a['mtNotificationsAvailable'] === true && $json !== '' && $a['mtNotificationToken'] === 'tok123'
                    && !str_contains($json, '</script>') && (json_decode($json, true)['items'][0]['id'] ?? 0) === 3 && $a['mtNotificationIcon'] !== '',
        'kaputt' => $json === '' && $a['mtNotificationToken'] === '' && $a['mtNotificationIcon'] === '',
    };
    echo $ok ? 'Variablen passen' : 'falsche Variablen: ' . json_encode(array_intersect_key($a, array_flip(['mtNotificationsAvailable', 'mtNotificationJson', 'mtNotificationToken', 'mtNotificationIcon'])));
    exit($ok ? 0 : 1);
}
