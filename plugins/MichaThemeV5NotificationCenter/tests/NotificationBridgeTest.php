<?php

declare(strict_types=1);

/**
 * Test Bridge ohne JTL: php plugins/MichaThemeV5NotificationCenter/tests/NotificationBridgeTest.php
 * Fake-DB auf SQLite im Speicher, gleiche SQL wie im Shop.
 */

namespace JTL\DB {
    // Mini-Ersatz für das JTL-Interface, nur was die Bridge braucht
    interface DbInterface
    {
        public function getObjects(string $sql, array $params = []): array;
    }
}

namespace {
    use JTL\DB\DbInterface;
    use Plugin\MichaThemeV5NotificationCenter\NotificationBridge;

    require_once __DIR__ . '/../src/NotificationBridge.php';

    final class SqliteDb implements DbInterface
    {
        public function __construct(public PDO $pdo)
        {
        }

        public function getObjects(string $sql, array $params = []): array
        {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);

            return $st->fetchAll(PDO::FETCH_OBJ);
        }
    }

    $fails = 0;
    $count = 0;
    function ok(string $name, bool $cond): void
    {
        global $fails, $count;
        $count++;
        echo ($cond ? 'OK   ' : 'FAIL ') . $name . "\n";
        if (!$cond) {
            $fails++;
        }
    }

    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE TABLE xplugin_mgd_notifications (id INTEGER PRIMARY KEY, scope TEXT, status TEXT, title TEXT, body TEXT, target_url TEXT, published_at TEXT, expires_at TEXT)");
    $pdo->exec('CREATE TABLE xplugin_mgd_notification_recipients (id INTEGER PRIMARY KEY, notification_id INT, customer_id INT, read_at TEXT, dismissed_at TEXT)');

    $past = gmdate('Y-m-d H:i:s', time() - 3600);
    $future = gmdate('Y-m-d H:i:s', time() + 3600);
    $ins = $pdo->prepare('INSERT INTO xplugin_mgd_notifications (id, scope, status, title, body, target_url, published_at, expires_at) VALUES (?,?,?,?,?,?,?,?)');
    $ins->execute([1, 'public', 'published', 'Öffentlich live', 'x', null, $past, null]);
    $ins->execute([2, 'public', 'published', 'Öffentlich abgelaufen', 'x', null, $past, $past]);
    $ins->execute([3, 'public', 'draft', 'Entwurf', 'x', null, $past, null]);
    $ins->execute([4, 'public', 'published', 'Später', 'x', null, $future, null]);
    $ins->execute([5, 'personal', 'published', 'Persönlich live', 'x', '/konto', $past, $future]);
    $ins->execute([6, 'personal', 'published', 'Persönlich abgelaufen', 'x', null, $past, $past]);
    $ins->execute([7, 'personal', 'published', 'Persönlich ausgeblendet', 'x', null, $past, null]);
    $ins->execute([8, 'personal', 'published', 'Anderer Kunde', 'x', null, $past, null]);
    $rec = $pdo->prepare('INSERT INTO xplugin_mgd_notification_recipients (notification_id, customer_id, dismissed_at) VALUES (?,?,?)');
    $rec->execute([5, 42, null]);
    $rec->execute([6, 42, null]);
    $rec->execute([7, 42, $past]);
    $rec->execute([8, 99, null]);

    $db = new SqliteDb($pdo);
    $titles = static fn (array $l): array => array_column($l, 'title');

    $r = NotificationBridge::forTemplate($db, 42);
    ok('öffentlich: nur live', $titles($r['public']) === ['Öffentlich live']);
    ok('persönlich: nur live, nicht abgelaufen, nicht ausgeblendet, nur eigener Kunde', $titles($r['personal']) === ['Persönlich live']);
    ok('Link bleibt erhalten', $r['personal'][0]['target_url'] === '/konto');
    ok('leerer Link als Text', $r['public'][0]['target_url'] === '');

    $r = NotificationBridge::forTemplate($db, 0);
    ok('Gast: keine persönlichen Meldungen', $r['personal'] === [] && count($r['public']) === 1);

    for ($i = 100; $i < 130; $i++) {
        $ins->execute([$i, 'public', 'published', 'Viele ' . $i, 'x', null, $past, null]);
    }
    $r = NotificationBridge::forTemplate($db, 0);
    ok('Obergrenze ' . NotificationBridge::LIMIT, count($r['public']) === NotificationBridge::LIMIT);

    echo "\n$count Prüfungen, $fails Fehler\n";
    exit($fails === 0 ? 0 : 1);
}
