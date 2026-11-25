<?php

declare(strict_types=1);

/**
 * Test Plugin-Baukasten (Brückenversion 2.1.2), läuft ohne JTL und ohne Netz: php tests/bruecke-baukasten.php
 * JTL-Klassen sind Mini-Stubs, HTTP ist ein Fake. Es wird nie echt gemeldet.
 */

namespace JTL\Events {
    if (!class_exists(Dispatcher::class, false)) {
        // Mini-Stub, merkt sich Event + Listener
        class Dispatcher
        {
            /** @var array<string,list<callable>> */
            public array $listeners = [];

            public function listen(string $event, callable $listener): void
            {
                $this->listeners[$event][] = $listener;
            }
        }
    }
}

namespace JTL\Plugin {
    if (!class_exists(Bootstrapper::class, false)) {
        // Signaturen wie JTL 5.8 (ohne Rückgabetyp), getPlugin liefert das Test-Plugin
        abstract class Bootstrapper
        {
            public static ?object $plugin = null;
            public static int $parentUninstalled = 0;

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

            public function uninstalled(bool $deleteData = true)
            {
                self::$parentUninstalled++;
            }

            public function getPlugin()
            {
                return self::$plugin;
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

            public static function getURL(): string
            {
                return 'https://Shop.Example.test';
            }
        }
    }
}

// Spion für den ALTEN Ping: wenn der Bootstrap noch InstallPing nimmt, zählt das hier mit (Klasse ist vor dem Autoload da)
namespace Plugin\MichaThemeV5\Support {
    final class InstallPing
    {
        public static int $calls = 0;

        public static function ping(bool $force = false, ?string $version = null): void
        {
            self::$calls++;
        }

        public static function client(?string $version = null, ?string $dir = null): ?object
        {
            self::$calls++;

            return null;
        }
    }
}

namespace {
    use Plugin\MichaThemeV5\Kit\Install\{InstallClient, JsonFileStore, Response, Transport};
    use Plugin\MichaThemeV5\Kit\Platform\Jtl\{Config, Guard, JtlKit, Setup};
    use Plugin\MichaThemeV5\Support\{InstallPing, KitSetup, UpdateData};

    $tmp = sys_get_temp_dir() . '/mgd-bruecke-test-' . bin2hex(random_bytes(4));
    mkdir($tmp . '/templates_c', 0700, true);
    define('PFAD_ROOT', $tmp . '/');
    define('PFAD_COMPILEDIR', 'templates_c/');
    define('URL_SHOP', 'https://Shop.Example.test/');

    $plugin = realpath(__DIR__ . '/../plugins/MichaThemeV5');
    require_once $plugin . '/src/autoload.php';

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

    // Fake-Transport für den Kit (merkt sich alles)
    final class KitFake implements Transport
    {
        /** @var list<array{0:string,1:string}> */
        public array $sent = [];

        public function __construct(public Response $answer = new Response(200, [], '{}'))
        {
        }

        public function send(string $method, string $url, array $headers): Response
        {
            $this->sent[] = [$method, $url];

            return $this->answer;
        }
    }

    // Test-Plugin wie das JTL-Objekt (getMeta()->getVersion(), getPaths()->getBasePath())
    $pluginObj = new class($plugin) {
        public function __construct(private string $base)
        {
        }

        public function getMeta(): object
        {
            return new class {
                public function getVersion(): string
                {
                    return '2.1.2';
                }
            };
        }

        public function getPaths(): object
        {
            $b = $this->base;

            return new class($b) {
                public function __construct(private string $b)
                {
                }

                public function getBasePath(): string
                {
                    return $this->b;
                }
            };
        }
    };
    \JTL\Plugin\Bootstrapper::$plugin = $pluginObj;

    // 1. Menü in info.xml
    $xml = simplexml_load_file($plugin . '/info.xml');
    $names = [];
    $files = [];
    foreach ($xml->Install->Adminmenu->Customlink as $l) {
        $names[] = (string) $l->Name;
        $files[(string) $l->Name] = (string) $l->Filename;
    }
    ok('Menü hat Übersicht', ($files['Übersicht'] ?? '') === 'uebersicht.php');
    ok('Menü hat Weitere Plugins', ($files['Weitere Plugins'] ?? '') === 'weitere-plugins.php');
    ok('alter Tab Updates nicht mehr angemeldet', !in_array('Updates', $names, true) && !in_array('updates.php', $files, true));
    ok('alter Tab Plugin-Empfehlungen nicht mehr angemeldet', !in_array('Plugin-Empfehlungen', $names, true) && !in_array('plugin-empfehlungen.php', $files, true));
    ok('Einstellungen, Lizenzen, Info, Demo-Inhalte bleiben', ($files['Einstellungen'] ?? '') === 'einstellungen.php' && ($files['Lizenzen'] ?? '') === 'lizenzen.php' && ($files['Info'] ?? '') === 'info.php' && ($files['Demo-Inhalte'] ?? '') === 'demo.php');
    ok('Menü: genau 6 Punkte', count($names) === 6);
    $allFiles = true;
    foreach ($files as $f) {
        $allFiles = $allFiles && is_file($plugin . '/adminmenu/' . $f);
    }
    ok('jede Menü-Datei gibt es', $allFiles);
    ok('alte Dateien liegen noch (Löschen erst Endversion)', is_file($plugin . '/adminmenu/updates.php') && is_file($plugin . '/adminmenu/plugin-empfehlungen.php') && is_file($plugin . '/src/Generated/CatalogFeed.php') && is_file($plugin . '/src/Update/Admin/UpdatesPage.php'));
    $u = (string) file_get_contents($plugin . '/adminmenu/uebersicht.php');
    $w = (string) file_get_contents($plugin . '/adminmenu/weitere-plugins.php');
    ok('uebersicht.php ruft renderTab mit Übersicht', str_contains($u, "renderTab(KitSetup::forShop(\$oPlugin), 'Übersicht')") && str_contains($u, '$oPlugin instanceof PluginInterface'));
    ok('weitere-plugins.php ruft renderTab mit Weitere Plugins', str_contains($w, "renderTab(KitSetup::forShop(\$oPlugin), 'Weitere Plugins')") && str_contains($w, '$oPlugin instanceof PluginInterface'));

    // 2. Namensraum + Werte aus Config
    $nsOk = true;
    $n = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($plugin . '/Kit', FilesystemIterator::SKIP_DOTS)) as $f) {
        if (!str_ends_with($f->getPathname(), '.php')) {
            continue;
        }
        $n++;
        $src = (string) file_get_contents($f->getPathname());
        $nsOk = $nsOk && preg_match('/^namespace Plugin\\\\MichaThemeV5\\\\Kit\\\\?[A-Za-z\\\\]*;/m', $src) === 1 && !str_contains($src, 'namespace MGD');
    }
    ok("Namensraum Plugin\\MichaThemeV5\\Kit in allen $n Kit-Dateien", $n > 20 && $nsOk);
    ok('Config: Produkt, PluginID, Katalog-Kennung', Config::PRODUCT === 'michatheme-v5' && Config::PLUGIN_ID === 'MichaThemeV5' && Config::CATALOG_SLUG === 'plugin-michatheme-v5-jtl' && Config::NAME === 'MichaThemeV5');
    ok('Katalog-Kennung = SELF_ID des alten Katalogs', Config::CATALOG_SLUG === \Plugin\MichaThemeV5\Catalog\CatalogService::SELF_ID);
    ok('Produktname gleich alter Update-Client', Config::PRODUCT === \Plugin\MichaThemeV5\Admin\UpdatesPage::PRODUCT);
    ok('Lizenz-Baustein aus (kein licenseEnabled)', !isset(KitSetup::options()['licenseEnabled']) && !str_contains((string) file_get_contents($plugin . '/src/Support/KitSetup.php'), 'licenseEnabled\' =>'));
    ok('root-keys.json im Plugin (öffentliche Schlüssel)', is_file($plugin . '/update/root-keys.json') && !str_contains((string) file_get_contents($plugin . '/update/root-keys.json'), 'PRIVATE'));

    // 3. Datenordner: alte install.json wird weiterbenutzt
    ok('dataFolder = alter Ordner', KitSetup::options()['dataFolder'] === 'mgd_update_michathemev5' && UpdateData::FOLDER === 'mgd_update_michathemev5');
    $old = $tmp . '/templates_c/mgd_update_michathemev5';
    mkdir($old, 0700, true);
    $oldState = ['report' => true, 'install_id' => '3f2b8c1e-9d4a-4b6e-8a1c-0e5d7f9a2b3c', 'key' => str_repeat('k', 32), 'last_ping' => 1_790_000_000];
    file_put_contents($old . '/install.json', json_encode($oldState));
    file_put_contents($old . '/state.json', json_encode(['enabled' => true]));
    $before = (string) file_get_contents($old . '/install.json');
    $kit = KitSetup::forShop($pluginObj);
    ok('forShop liefert Kit', $kit instanceof JtlKit && $kit->version === '2.1.2' && $kit->product === 'michatheme-v5');
    ok('Kit-Status liest alte install.json (Zuletzt gemeldet)', $kit?->installClient()?->status() === 'Zuletzt gemeldet: ' . gmdate('d.m.Y', 1_790_000_000) . '.' && $kit->installClient()->isEnabled());
    ok('kein neuer Datenordner nebenan', !is_dir($tmp . '/templates_c/michathemev5_update'));
    ok('install.json unverändert (ID + Schlüssel bleiben)', (string) file_get_contents($old . '/install.json') === $before);
    ok('state.json (Update-Einstellung) unverändert', json_decode((string) file_get_contents($old . '/state.json'), true) === ['enabled' => true]);
    // ohne dataFolder wäre es der Standard-Ordner gewesen (Beweis, warum die Option nötig ist)
    $std = Setup::forShop($pluginObj);
    ok('ohne dataFolder: anderer Ordner michathemev5_update', $std !== null && is_dir($tmp . '/templates_c/michathemev5_update') && $std->installClient()?->status() === 'Noch nicht gemeldet.');
    // adoptLegacy: alter mgd-update-client-Ordner überschreibt nichts, was schon da ist
    $legacy = $tmp . '/templates_c/mgd-update-client/MichaThemeV5';
    mkdir($legacy, 0700, true);
    file_put_contents($legacy . '/install.json', json_encode(['report' => false, 'install_id' => 'ffffffff-ffff-4fff-8fff-ffffffffffff']));
    KitSetup::forShop($pluginObj);
    ok('adoptLegacy überschreibt vorhandene install.json nicht', (string) file_get_contents($old . '/install.json') === $before);
    ok('adoptLegacy legt alten Ordner still (.migrated)', is_file($legacy . '/install.json.migrated') && !is_file($legacy . '/install.json'));
    // gleiche Datei auch bei direktem Bau (Test-Transport): ID aus der alten Datei geht in den Header
    $fake = new KitFake(new Response(200, [], '{}'));
    $guardOn = new Guard(static fn (): bool => true, static fn (string $t): bool => $t === 'TOK', static fn (): string => '<input type="hidden" name="jtl_token" value="TOK">');
    $direct = new JtlKit('michatheme-v5', 'MichaThemeV5', '2.1.2', $old, $guardOn, 'shop.example.test', 'plugin-michatheme-v5-jtl', '', [], false, null, $fake);
    $direct->ping(true);
    $sentSt = json_decode((string) file_get_contents($old . '/install.json'), true);
    ok('Meldung nutzt dieselbe Install-ID', count($fake->sent) === 1 && ($sentSt['install_id'] ?? '') === $oldState['install_id'] && ($sentSt['key'] ?? '') === $oldState['key']);

    // 4. Bootstrap: alter Ping läuft nicht doppelt, nichts wirft
    require_once $plugin . '/Bootstrap.php';
    $bsrc = (string) file_get_contents($plugin . '/Bootstrap.php');
    ok('Bootstrap nennt InstallPing nicht mehr', !str_contains($bsrc, 'InstallPing') && !str_contains($bsrc, 'Install\\Platform'));
    ok('Bootstrap nutzt Kit-Ping (ping($force)) und uninstall($deleteData)', str_contains($bsrc, '?->ping($force)') && str_contains($bsrc, '?->uninstall($deleteData)'));
    // Schalter aus > Kit meldet nix, auch kein Netz
    file_put_contents($old . '/install.json', json_encode(['report' => false]));
    $off = (string) file_get_contents($old . '/install.json');
    $boot = (new ReflectionClass(\Plugin\MichaThemeV5\Bootstrap::class))->newInstanceWithoutConstructor();
    $thrown = false;
    try {
        $boot->installed();
        $boot->enabled();
        $boot->updated('1.5.2', '2.1.2');
        $boot->updated(null, null);
    } catch (\Throwable $e) {
        $thrown = true;
        echo '     ' . $e->getMessage() . "\n";
    }
    ok('installed/enabled/updated werfen nie', !$thrown);
    ok('alter Ping (InstallPing) lief dabei nie', InstallPing::$calls === 0);
    \JTL\Shop::$frontend = false;
    $_SERVER['SCRIPT_NAME'] = '/admin/index.php'; // Backend (Setup::isBackend)
    $d = new \JTL\Events\Dispatcher();
    $boot->boot($d);
    ok('Backend: ein Hook backend.notification', count($d->listeners['backend.notification'] ?? []) === 1);
    foreach ($d->listeners['backend.notification'] as $cb) {
        $cb();
        $cb();
    }
    ok('Hook im Backend: alter Ping lief nie', InstallPing::$calls === 0);
    ok('Schalter aus: install.json bleibt, nichts gesendet', (string) file_get_contents($old . '/install.json') === $off);
    \JTL\Shop::$frontend = true;
    $_SERVER['SCRIPT_NAME'] = '/index.php'; // Shop, kein Backend (Setup::isBackend)
    $d = new \JTL\Events\Dispatcher();
    $boot->boot($d);
    ok('Frontend: kein Hook', ($d->listeners['backend.notification'] ?? []) === []);
    \JTL\Shop::$frontend = false;
    $_SERVER['SCRIPT_NAME'] = '/admin/index.php'; // Backend (Setup::isBackend)
    // kaputtes Plugin-Objekt: Installation scheitert trotzdem nie
    \JTL\Plugin\Bootstrapper::$plugin = null;
    $thrown = false;
    try {
        $boot->installed();
        $boot->enabled();
        $boot->updated('1.5.2', '2.1.2');
    } catch (\Throwable) {
        $thrown = true;
    }
    ok('ohne Plugin-Objekt: installed/enabled/updated werfen nie', !$thrown);

    // 5. uninstalled(): wirft nie
    $thrown = false;
    $parent = \JTL\Plugin\Bootstrapper::$parentUninstalled;
    try {
        $boot->uninstalled(true);
        $boot->uninstalled(false);
        $boot->uninstalled();
    } catch (\Throwable) {
        $thrown = true;
    }
    ok('uninstalled ohne Plugin-Objekt wirft nie, Eltern-Methode läuft', !$thrown && \JTL\Plugin\Bootstrapper::$parentUninstalled === $parent + 3);
    \JTL\Plugin\Bootstrapper::$plugin = $pluginObj;
    $boot->uninstalled(false);
    ok('uninstalled(false): Datenordner + Schalter bleiben', is_dir($old) && (string) file_get_contents($old . '/install.json') !== '' && json_decode((string) file_get_contents($old . '/install.json'), true)['report'] === false);
    $boot->uninstalled(true);
    ok('uninstalled(true): Datenordner weg', !is_dir($old));
    // Datenordner ist eine Datei statt Ordner > trotzdem kein Fehler
    file_put_contents($old, 'kein Ordner');
    $thrown = false;
    try {
        $boot->uninstalled(true);
        $boot->installed();
    } catch (\Throwable) {
        $thrown = true;
    }
    ok('kaputter Datenordner: uninstalled/installed werfen nie', !$thrown);
    @unlink($old);
    // Server weg beim Abmelden: Transport wirft, Deinstallieren läuft durch
    mkdir($old, 0700, true);
    file_put_contents($old . '/install.json', json_encode($oldState));
    $boom = new class implements Transport {
        public function send(string $method, string $url, array $headers): Response
        {
            throw new \RuntimeException('Server weg');
        }
    };
    $k2 = new JtlKit('michatheme-v5', 'MichaThemeV5', '2.1.2', $old, $guardOn, 'shop.example.test', 'x', '', [], false, null, $boom);
    $thrown = false;
    try {
        $r = $k2->uninstall(true);
    } catch (\Throwable) {
        $thrown = true;
    }
    ok('Abmelden bei totem Server wirft nie, Ordner weg', !$thrown && !is_dir($old));

    // 6. Übersicht: nur POST mit Token speichert den Schalter
    mkdir($old, 0700, true);
    file_put_contents($old . '/install.json', json_encode(['report' => true]));
    $fake = new KitFake();
    $guardNo = new Guard(static fn (): bool => false, static fn (): bool => true, static fn (): string => '');
    $mk = static fn (Guard $g): JtlKit => new JtlKit('michatheme-v5', 'MichaThemeV5', '2.1.2', $old, $g, 'shop.example.test', 'plugin-michatheme-v5-jtl', '', [], false, null, $fake);
    $field = \Plugin\MichaThemeV5\Kit\Install\Settings::fieldName($mk($guardOn)->installClient());
    $action = \Plugin\MichaThemeV5\Kit\Install\Settings::actionName($mk($guardOn)->installClient());
    $isOn = static fn (): bool => (json_decode((string) file_get_contents($GLOBALS['old'] . '/install.json'), true)['report'] ?? true) !== false;
    $off = [$action => '1'];
    $mk($guardOn)->page('GET', $off + ['jtl_token' => 'TOK'], '2026-11-24');
    ok('GET speichert nichts (auch mit Token)', $isOn());
    $mk($guardOn)->page('POST', $off, '2026-11-24');
    ok('POST ohne Token speichert nichts', $isOn());
    $mk($guardOn)->page('POST', $off + ['jtl_token' => 'falsch'], '2026-11-24');
    ok('POST mit falschem Token speichert nichts', $isOn());
    $mk($guardNo)->page('POST', $off + ['jtl_token' => 'TOK'], '2026-11-24');
    ok('POST ohne Recht speichert nichts', $isOn());
    $res = $mk($guardNo)->page('GET', [], '2026-11-24');
    ok('ohne Recht: nur Hinweis, keine Bereiche', str_contains($res['html'], 'fehlt die Berechtigung') && !str_contains($res['html'], 'Installation melden'));
    $mk($guardOn)->page('POST', $off + ['jtl_token' => 'TOK'], '2026-11-24');
    ok('POST mit Token + Recht: Schalter aus', !$isOn());
    $page = $mk($guardOn)->page('GET', [], '2026-11-24');
    $h = $page['html'];
    ok('Übersicht zeigt Info, Updates, Installation melden', str_contains($h, 'Version: 2.1.2') && str_contains($h, '>Updates<') && str_contains($h, '>Installation melden<'));
    ok('Übersicht: kein Lizenz-Bereich', !str_contains($h, '>Lizenz<') && !str_contains($h, 'mgd-kit-h-license'));
    ok('Übersicht: Hinweistext aus Baukasten', str_contains($h, htmlspecialchars(\Plugin\MichaThemeV5\Kit\Install\Settings::HINT, ENT_QUOTES)));
    ok('Tab Weitere Plugins ohne Recht gesperrt', str_contains($mk($guardNo)->recommendationsPage(), 'fehlt die Berechtigung'));
    ok('Setup::renderTab: fremder Tabname = null', Setup::renderTab($mk($guardOn), 'Updates') === null && Setup::renderTab($mk($guardOn), 'Plugin-Empfehlungen') === null);
    ok('Setup::renderTab: Kit null = freundlicher Text', str_contains((string) Setup::renderTab(null, 'Übersicht'), 'Der Shop läuft normal weiter'));

    // 7. Weitere Plugins: eigenes Plugin raus, Suche + Notification Center bleiben (gleiches Repo!)
    $feed = (string) json_encode(['schemaVersion' => 1, 'items' => [
        ['id' => 'plugin-michatheme-v5-jtl', 'name' => 'MichaTheme V5 Plugin', 'platform' => 'jtl', 'status' => 'live', 'repository' => 'https://github.com/MichaelGahnDESIGN/MichaThemeV5_JTL-Shop-5'],
        ['id' => 'plugin-suche-jtl', 'name' => 'MGD Suche', 'platform' => 'jtl', 'status' => 'live', 'repository' => 'https://github.com/MichaelGahnDESIGN/MichaThemeV5_JTL-Shop-5'],
        ['id' => 'plugin-notification-center-jtl', 'name' => 'MGD Notification Center', 'platform' => 'jtl', 'status' => 'live', 'repository' => 'https://github.com/MichaelGahnDESIGN/MichaThemeV5_JTL-Shop-5'],
    ]]);
    $feedHttp = new KitFake(new Response(200, [], $feed));
    $recWith = static function (string $repo) use ($old, $guardOn, $feedHttp): array {
        @unlink($old . '/kit-recommendations.json');
        $k = new JtlKit('michatheme-v5', 'MichaThemeV5', '2.1.2', $old, $guardOn, 'shop.example.test', 'plugin-michatheme-v5-jtl', $repo, [], false, null, $feedHttp);

        return array_column($k->recommendations()->load()['items'], 'slug');
    };
    ok('mit repo-Wert aus KitSetup (leer): Suche + NC sichtbar, eigenes weg', $recWith(KitSetup::options()['repo']) === ['plugin-suche-jtl', 'plugin-notification-center-jtl']);
    ok('Kit 1.15: auch mit Config::REPO bleiben Suche + NC sichtbar (Sammelrepo)', $recWith(Config::REPO) === ['plugin-suche-jtl', 'plugin-notification-center-jtl']);

    // 8. Version überall 2.1.2
    $tpl = simplexml_load_file(__DIR__ . '/../templates/MichaThemeV5/template.xml');
    $ver = include $plugin . '/src/Generated/version.php';
    $schema = json_decode((string) file_get_contents($plugin . '/src/Generated/mt-admin-schema.json'), true);
    ok('Version 2.1.2 in info.xml, template.xml, version.php, Schema', (string) $xml->Version === '2.1.2' && (string) $tpl->Version === '2.1.2' && $ver['version'] === '2.1.2' && ($schema['version'] ?? '') === '2.1.2');

    // aufräumen (nur eigener Temp-Ordner)
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) {
        $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($tmp);

    echo "\n$count Prüfungen, $fails Fehler\n";
    exit($fails === 0 ? 0 : 1);
}
