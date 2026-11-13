<?php

declare(strict_types=1);

/**
 * Test Backend-Meldungen ohne JTL: php plugins/MichaThemeV5NotificationCenter/tests/NotificationAdminTest.php
 */

namespace JTL\DB {
    interface DbInterface
    {
        public function getObjects(string $sql, array $params = []): array;

        public function getAffectedRows(string $stmt, array $params = []): int;

        public function insert(string $tableName, object $object, bool $echo = false): int;
    }
}

namespace {
    use JTL\DB\DbInterface;
    use Plugin\MichaThemeV5NotificationCenter\NotificationAdmin;
    use Plugin\MichaThemeV5NotificationCenter\NotificationBridge;

    require_once __DIR__ . '/../src/NotificationAdmin.php';
    require_once __DIR__ . '/../src/NotificationBridge.php';

    // SQLite statt MySQL
    final class Db implements DbInterface
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

        public function getAffectedRows(string $stmt, array $params = []): int
        {
            $st = $this->pdo->prepare($stmt);
            $st->execute($params);

            return $st->rowCount();
        }

        public function insert(string $tableName, object $object, bool $echo = false): int
        {
            // wie JTL NiceDB: null > '' (MySQL ohne strict = 0000-00-00), '_DBNULL_' > echtes NULL
            $data = array_map(static fn ($v) => $v === '_DBNULL_' ? null : ($v === null ? '0000-00-00 00:00:00' : $v), (array) $object);
            $st = $this->pdo->prepare('INSERT INTO ' . $tableName . ' (' . implode(',', array_keys($data)) . ') VALUES (:' . implode(',:', array_keys($data)) . ')');
            $st->execute($data);

            return (int) $this->pdo->lastInsertId();
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

    date_default_timezone_set('Europe/Berlin');
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE xplugin_mgd_notifications (id INTEGER PRIMARY KEY AUTOINCREMENT, scope TEXT, status TEXT, source_key TEXT, title TEXT, body TEXT, target_url TEXT, published_at TEXT, expires_at TEXT, created_at TEXT)');
    $pdo->exec('CREATE TABLE xplugin_mgd_notification_recipients (id INTEGER PRIMARY KEY AUTOINCREMENT, notification_id INT, customer_id INT, read_at TEXT, dismissed_at TEXT)');
    $pdo->exec('CREATE TABLE tkunde (kKunde INTEGER PRIMARY KEY, cMail TEXT)');
    $pdo->exec("INSERT INTO tkunde VALUES (42, 'kunde@example.test')");
    $db = new Db($pdo);
    $admin = new NotificationAdmin($db);

    // Text säubern
    $clean = NotificationAdmin::cleanBody('<p onclick="x()">Hallo <strong>Welt</strong> <a href="javascript:alert(1)">böse</a> <a href="https://example.test/a" target="_blank">gut</a></p><script>alert(1)</script><img src=x onerror=1><div>Rest</div>');
    ok('Script und Bild weg', !str_contains($clean, 'script') && !str_contains($clean, '<img') && !str_contains($clean, 'alert'));
    ok('Attribute weg, nur href', !str_contains($clean, 'onclick') && !str_contains($clean, 'target='));
    ok('guter Link bleibt, böser wird Text', str_contains($clean, '<a href="https://example.test/a">gut</a>') && str_contains($clean, 'böse') && !str_contains($clean, 'javascript'));
    ok('Formatierung bleibt, div wird Text', str_contains($clean, '<strong>Welt</strong>') && str_contains($clean, 'Rest') && !str_contains($clean, '<div'));
    ok('Umlaute bleiben', str_contains(NotificationAdmin::cleanBody('Grüße'), 'Grüße'));

    // Speichern
    ok('ohne Titel: Fehler', $admin->save(['title' => ' '])['ok'] === false);
    ok('falscher Link: Fehler', $admin->save(['title' => 'X', 'target_url' => 'javascript:x'])['ok'] === false);
    ok('persönlich ohne Kunde: Fehler', $admin->save(['title' => 'X', 'scope' => 'personal', 'customer' => '999'])['ok'] === false);
    $r = $admin->save(['title' => 'Öffentlich', 'body' => '<p>Text</p>', 'status' => 'published', 'target_url' => '/neu']);
    ok('öffentlich gespeichert', $r['ok'] === true && $r['id'] > 0);
    $p = $admin->save(['title' => 'Für Kunde', 'body' => 'Hallo', 'scope' => 'personal', 'customer' => 'kunde@example.test', 'status' => 'published']);
    ok('persönlich per E-Mail gespeichert', $p['ok'] === true);
    $found = $admin->find($p['id']);
    ok('Empfänger gesetzt', (int) $found['customer_id'] === 42);
    $view = NotificationBridge::forTemplate($db, 42);
    ok('Glocke zeigt beide', count($view['public']) === 1 && count($view['personal']) === 1 && $view['public'][0]['body'] === '<p>Text</p>');
    ok('Gast sieht nur öffentlich', NotificationBridge::forTemplate($db, 0)['personal'] === []);

    // Entwurf + geplant unsichtbar
    $admin->save(['title' => 'Entwurf', 'status' => 'draft']);
    $admin->save(['title' => 'Später', 'status' => 'published', 'published_at' => date('Y-m-d\TH:i', time() + 7200)]);
    ok('Entwurf und geplant unsichtbar', count(NotificationBridge::forTemplate($db, 0)['public']) === 1);

    // Ohne Ablaufdatum: wirklich NULL, Meldung sichtbar (Fehler aus 0.7.0)
    $noExp = $admin->save(['title' => 'Ohne Ablauf', 'status' => 'published']);
    $raw = $pdo->query('SELECT expires_at, published_at FROM xplugin_mgd_notifications WHERE id = ' . (int) $noExp['id'])->fetch(PDO::FETCH_OBJ);
    ok('leeres Ablaufdatum = NULL', $raw->expires_at === null);
    ok('Meldung ohne Ablaufdatum ist in der Glocke', in_array('Ohne Ablauf', array_column(NotificationBridge::forTemplate($db, 0)['public'], 'title'), true));
    $draft = $admin->save(['title' => 'Entwurf ohne Datum', 'status' => 'draft']);
    ok('Entwurf ohne Datum: published_at NULL', $pdo->query('SELECT published_at FROM xplugin_mgd_notifications WHERE id = ' . (int) $draft['id'])->fetchColumn() === null);
    $admin->save(['id' => $noExp['id'], 'title' => 'Ohne Ablauf', 'status' => 'archived']);
    $admin->save(['id' => $draft['id'], 'title' => 'Entwurf ohne Datum', 'status' => 'archived']);

    // Bearbeiten ohne Kundenwechsel: gelesen/ausgeblendet bleibt
    $pdo->exec("UPDATE xplugin_mgd_notification_recipients SET read_at = '2026-11-12 10:00:00', dismissed_at = '2026-11-12 11:00:00' WHERE notification_id = " . (int) $p['id']);
    $admin->save(['id' => $p['id'], 'title' => 'Für Kunde (korrigiert)', 'scope' => 'personal', 'customer' => '42', 'status' => 'published']);
    $state = $pdo->query('SELECT read_at, dismissed_at FROM xplugin_mgd_notification_recipients WHERE notification_id = ' . (int) $p['id'])->fetch(PDO::FETCH_OBJ);
    ok('Titel korrigiert: Lesestatus bleibt', $state && $state->read_at !== null && $state->dismissed_at !== null);

    // Bearbeiten: persönlich > öffentlich entfernt Empfänger
    $admin->save(['id' => $p['id'], 'title' => 'Jetzt öffentlich', 'scope' => 'public', 'status' => 'published']);
    ok('Empfänger entfernt', $admin->find($p['id'])['customer_id'] === null);
    ok('Zeitzone: Eingabe Berlin > UTC', (function () use ($admin): bool {
        $r = $admin->save(['title' => 'Zeit', 'status' => 'draft', 'published_at' => '2026-07-01T12:00']);
        return $admin->find($r['id'])['published_at'] === '2026-07-01 10:00:00';
    })());

    // Bearbeiten mit gleichem Datum ohne Sekunden: Sekunden bleiben
    $pdo->exec("UPDATE xplugin_mgd_notifications SET published_at = '2026-11-12 09:15:37' WHERE id = " . (int) $r['id']);
    $admin->save(['id' => $r['id'], 'title' => 'Zeit korrigiert', 'status' => 'published', 'published_at' => date('Y-m-d\TH:i', (int) strtotime('2026-11-12 09:15:37 UTC'))]);
    ok('Sekunden bleiben beim Bearbeiten', $admin->find($r['id'])['published_at'] === '2026-11-12 09:15:37');
    $admin->save(['id' => $r['id'], 'title' => 'Zeit', 'status' => 'draft', 'published_at' => '2026-07-01T12:00']);

    // Seiten
    for ($i = 0; $i < 60; $i++) {
        $admin->save(['title' => 'Viele ' . $i, 'status' => 'draft']);
    }
    ok('Seite 1 hat 50, Seite 2 den Rest', count($admin->latest(50, 1)) === 50 && count($admin->latest(50, 2)) === $admin->total() - 50);

    // Archivieren
    ok('archivieren', $admin->archive($r['id']) === true && $admin->find($r['id'])['status'] === 'archived');
    ok('Liste neueste zuerst', $admin->latest()[0]['title'] === 'Viele 59');

    echo "\n$count Prüfungen, $fails Fehler\n";
    exit($fails === 0 ? 0 : 1);
}
