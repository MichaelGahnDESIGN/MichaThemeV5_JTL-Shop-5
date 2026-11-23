<?php

declare(strict_types=1);

/**
 * Test Install-Client (Free-Lizenz Aktiv-Meldung), läuft ohne JTL und ohne Netz: php tests/install-client.php
 * Fake-Transport statt cURL, JTL-Klassen als Mini-Stubs. Kein echter Abruf.
 */

namespace JTL\Events {
    if (!class_exists(Dispatcher::class, false)) {
        // Mini-Stub, merkt sich nur die Events
        class Dispatcher
        {
            public array $events = [];

            public function listen(string $event, callable $listener): void
            {
                $this->events[] = $event;
            }
        }
    }
}

namespace JTL\Plugin {
    if (!class_exists(Bootstrapper::class, false)) {
        // Signaturen wie JTL 5.8 (ohne Rückgabetyp)
        abstract class Bootstrapper
        {
            public function boot(\JTL\Events\Dispatcher $dispatcher)
            {
            }

            public function installed()
            {
            }

            public function enabled()
            {
            }

            public function updated($oldVersion, $newVersion)
            {
            }
        }
    }
}

namespace JTL {
    if (!class_exists(Shop::class, false)) {
        class Shop
        {
            public static bool $frontend = false;

            public static function isFrontend(): bool
            {
                return self::$frontend;
            }
        }
    }
}

namespace {
    use Plugin\MichaThemeV5\Install\{InstallClient, JsonFileStore, Response, Settings, Transport};
    use Plugin\MichaThemeV5\Install\Platform\Jtl;
    use Plugin\MichaThemeV5\Support\InstallPing;
    use Plugin\MichaThemeV5\Update\Admin\InstallReporting;
    use Plugin\MichaThemeV5\Update\Factory;

    $tmp = sys_get_temp_dir() . '/mgd-install-test-' . bin2hex(random_bytes(4));
    mkdir($tmp, 0700, true);
    define('PFAD_ROOT', $tmp . '/');
    define('PFAD_COMPILEDIR', 'templates_c/');
    define('URL_SHOP', 'https://Shop.Example.test/');

    $plugin = __DIR__ . '/../plugins/MichaThemeV5';
    spl_autoload_register(static function (string $c) use ($plugin): void {
        $p = 'Plugin\\MichaThemeV5\\';
        if (str_starts_with($c, $p)) {
            $f = $plugin . '/src/' . str_replace('\\', '/', substr($c, strlen($p))) . '.php';
            if (is_file($f)) {
                require_once $f;
            }
        }
    });

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

    // Fake-Transport, merkt sich alle Aufrufe
    final class FakeTransport implements Transport
    {
        /** @var list<array{0:string,1:string,2:array<string,string>}> */
        public array $sent = [];

        public function __construct(public Response $answer = new Response(200, [], '{}'))
        {
        }

        public function send(string $method, string $url, array $headers): Response
        {
            $this->sent[] = [$method, $url, $headers];

            return $this->answer;
        }
    }

    $now = 1_800_000_000;
    $clock = static function () use (&$now): int {
        return $now;
    };

    // 1. client(): gültig / ungültig
    $dir = $tmp . '/data';
    mkdir($dir, 0700, true);
    ok('Produktname wie Update-Datei', InstallPing::PRODUCT === 'michatheme-v5' && \Plugin\MichaThemeV5\Admin\UpdatesPage::PRODUCT === InstallPing::PRODUCT);
    ok('client() bei gültigem Produkt nicht null', Jtl::client($dir, 'michatheme-v5', '1.5.0') instanceof InstallClient);
    ok('InstallPing::client() nicht null', InstallPing::client('1.5.0', $dir) instanceof InstallClient);
    ok('client() bei ungültigem Produkt null', Jtl::client($dir, 'Micha Theme!', '1.5.0') === null);
    ok('client() bei leerem Produkt null', Jtl::client($dir, '', '1.5.0') === null);
    ok('Domain aus URL_SHOP, klein', Jtl::shopDomain() === 'shop.example.test');
    ok('Version aus version.php = 1.5.2', InstallPing::version() === '1.5.2');

    // 2. ping mit Fake-Transport: nur erlaubte Felder
    $http = new FakeTransport(new Response(200, ['x-mgd-install-key' => str_repeat('k', 32)], '{}'));
    $ic = new InstallClient($http, new JsonFileStore($dir . '/install.json'), 'michatheme-v5', 'jtl5', '1.5.0', 'shop.example.test', $clock);
    ok('Schalter ab Werk an', $ic->isEnabled());
    ok('ping(true) meldet', $ic->ping(true) === true && count($http->sent) === 1);
    [$m, $url, $h] = $http->sent[0];
    ok('GET auf theme.michael-gahn.de/api/v1/updates/michatheme-v5.json', $m === 'GET' && $url === 'https://theme.michael-gahn.de/api/v1/updates/michatheme-v5.json');
    ok('erste Meldung: genau die 4 Felder', array_keys($h) === ['X-MGD-Install-Id', 'X-MGD-Platform', 'X-MGD-Domain', 'X-MGD-Version']);
    ok('Felder Inhalt', $h['X-MGD-Platform'] === 'jtl5' && $h['X-MGD-Domain'] === 'shop.example.test' && $h['X-MGD-Version'] === '1.5.0');
    ok('Install-ID ist zufällige UUID', InstallClient::isUuid($h['X-MGD-Install-Id']));
    $id = $h['X-MGD-Install-Id'];

    // gleicher Tag ohne force: nix senden
    $now += 3600;
    ok('2. ping am selben Tag sendet nicht', $ic->ping() === false && count($http->sent) === 1);
    $now += InstallClient::DAY;
    ok('nach einem Tag wieder melden', $ic->ping() === true && count($http->sent) === 2);
    $h2 = $http->sent[1][2];
    ok('Folgemeldung: nur erlaubte Felder (+ eigener Schlüssel)', array_keys($h2) === ['X-MGD-Install-Id', 'X-MGD-Platform', 'X-MGD-Domain', 'X-MGD-Version', 'Authorization']);
    ok('gleiche Install-ID, Schlüssel aus Antwort', $h2['X-MGD-Install-Id'] === $id && $h2['Authorization'] === 'Bearer ' . str_repeat('k', 32));
    $all = '';
    foreach ($http->sent as [, $u, $hh]) {
        $all .= $u . json_encode($hh);
    }
    ok('keine Kunden-/Shopdaten im Request', preg_match('/mail|kunde|customer|order|bestell|@/i', $all) !== 1);

    // Plattform weg: Backoff, kein Dauerfeuer
    $down = new FakeTransport(new Response(503));
    $ic2 = new InstallClient($down, new JsonFileStore($dir . '/install2.json'), 'michatheme-v5', 'jtl5', '1.5.0', 'shop.example.test', $clock);
    $ic2->ping(true);
    ok('Plattform weg: kein zweiter Versuch sofort', $ic2->ping() === false && count($down->sent) === 1);

    // 3. Schalter aus: kein Senden mehr
    $ic->setEnabled(false);
    ok('Schalter aus: Eintrag wird gelöscht (DELETE)', end($http->sent)[0] === 'DELETE');
    $n = count($http->sent);
    $now += 2 * InstallClient::DAY;
    ok('Schalter aus: ping sendet nicht', $ic->ping() === false && $ic->ping(true) === false && count($http->sent) === $n);
    ok('Schalter aus: Status', $ic->status() === 'Meldung ist ausgeschaltet.' && !$ic->isEnabled());
    $state = json_decode((string) file_get_contents($dir . '/install.json'), true);
    ok('Schalter aus: ID und Schlüssel weg', is_array($state) && !isset($state['install_id']) && !isset($state['key']));
    ok('Schalter aus bleibt gespeichert', (new InstallClient($http, new JsonFileStore($dir . '/install.json'), 'michatheme-v5', 'jtl5', '1.5.0', 'x.test', $clock))->isEnabled() === false);

    // 4. Update-Seite: Schalter mit InstallReporting
    $calls = ['ping' => 0, 'set' => []];
    $on = true;
    $rep = new InstallReporting(
        static function () use (&$calls): void {
            $calls['ping']++;
        },
        static function () use (&$on): bool {
            return $on;
        },
        static function (bool $v) use (&$calls, &$on): void {
            $calls['set'][] = $v;
            $on = $v;
        },
        static fn (): string => 'Noch nicht gemeldet.',
        Settings::HINT,
    );
    $rk = $plugin . '/update/root-keys.json';
    $page = static fn (bool $csrf, bool $right) => Factory::page($rk, $tmp . '/upd', 'michatheme-v5', 'free', 'MichaThemeV5', '1.5.0', 'shop.example.test', '5.8.4', static fn (): bool => $csrf, static fn (): bool => $right, '<input type="hidden" name="jtl_token" value="T">', null, ['info.xml'], null, $rep);
    $r = $page(true, true)->handle('GET', []);
    ok('Seite zeigt Schalter (an)', str_contains($r['html'], 'Installation bei Michael Gahn DESIGN melden') && str_contains($r['html'], 'name="install_report" value="1" checked'));
    ok('Seite zeigt Hinweistext', str_contains($r['html'], htmlspecialchars(Settings::HINT, ENT_QUOTES)));
    ok('Aktiv-Meldung auch bei ausgeschalteter Update-Prüfung', $calls['ping'] === 1);
    $page(false, true)->handle('POST', ['mgd_update_action' => 'save_install']);
    ok('ohne CSRF: Schalter bleibt', $calls['set'] === []);
    $page(true, false)->handle('POST', ['mgd_update_action' => 'save_install']);
    ok('ohne Recht: Schalter bleibt, kein Ping', $calls['set'] === [] && $calls['ping'] === 1);
    $r = $page(true, true)->handle('POST', ['mgd_update_action' => 'save_install']);
    ok('Haken weg + Speichern = aus', $calls['set'] === [false] && !str_contains($r['html'], 'value="1" checked'));
    $names = array_map(static fn (ReflectionParameter $p): string => $p->getName(), (new ReflectionMethod(Factory::class, 'page'))->getParameters());
    ok('Factory::page: InstallReporting ist letzter Wert', end($names) === 'install');
    $r = Factory::page($rk, $tmp . '/upd', 'michatheme-v5', 'free', 'MichaThemeV5', '1.5.0', 'shop.example.test', '5.8.4', static fn (): bool => true, static fn (): bool => true, '<input>')->handle('GET', []);
    ok('ohne InstallReporting: kein Schalter (alter Weg geht weiter)', !str_contains($r['html'], 'install_report'));

    // 5. Bootstrap: Installation scheitert nie, Hook nur im Backend
    require_once $plugin . '/Bootstrap.php';
    // Datenordner kaputt machen (Datei statt Ordner) > client() null, kein Netz
    mkdir($tmp . '/templates_c', 0700, true);
    file_put_contents($tmp . '/templates_c/mgd_update_michathemev5', 'kein Ordner');
    ok('kaputter Datenordner: client() null', InstallPing::client() === null);
    $boot = (new ReflectionClass(\Plugin\MichaThemeV5\Bootstrap::class))->newInstanceWithoutConstructor();
    $thrown = false;
    try {
        $boot->installed();
        $boot->enabled();
        $boot->updated('1.4.0', '1.5.0');
        $boot->updated(null, null);
    } catch (\Throwable $e) {
        $thrown = true;
        echo '     ' . $e->getMessage() . "\n";
    }
    ok('installed/enabled/updated werfen nie', !$thrown);
    \JTL\Shop::$frontend = true;
    $d = new \JTL\Events\Dispatcher();
    $boot->boot($d);
    ok('Frontend: kein Install-Hook', !in_array(\Plugin\MichaThemeV5\Bootstrap::BACKEND_EVENT, $d->events, true));
    \JTL\Shop::$frontend = false;
    $d = new \JTL\Events\Dispatcher();
    $boot->boot($d);
    ok('Backend: Hook backend.notification angemeldet', in_array('backend.notification', $d->events, true));
    $src = (string) file_get_contents($plugin . '/Bootstrap.php');
    ok('Bootstrap nutzt ?-> bzw. try/catch', str_contains($src, 'catch (\Throwable)') && str_contains((string) file_get_contents($plugin . '/src/Support/InstallPing.php'), '?->ping('));

    // 6. Kurztext in info.xml, keine Geheimnisse im Client
    $xml = simplexml_load_file($plugin . '/info.xml');
    ok('info.xml Kurztext', str_contains((string) $xml->Description, 'Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar im Plugin unter „Updates“.'));
    ok('info.xml Version 1.5.2', (string) $xml->Version === '1.5.2');
    $bad = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($plugin . '/src/Install', FilesystemIterator::SKIP_DOTS)) as $f) {
        if (preg_match('/BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY|sk_live|ghp_[A-Za-z0-9]{20}|MGD\\\\InstallClient/', (string) file_get_contents($f->getPathname()))) {
            $bad++;
        }
    }
    ok('Install-Client: kein Schlüssel, Namensraum umgeschrieben', $bad === 0);

    // aufräumen (nur eigener Temp-Ordner)
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) {
        $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($tmp);

    echo "\n$count Prüfungen, $fails Fehler\n";
    exit($fails === 0 ? 0 : 1);
}
