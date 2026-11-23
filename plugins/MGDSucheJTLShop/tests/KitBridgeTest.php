<?php

declare(strict_types=1);

/**
 * Brückenversion Test, läuft ohne JTL: php plugins/MGDSucheJTLShop/tests/KitBridgeTest.php
 * Prüft: Baukasten nimmt den alten Datenordner mgd_suche_update, alte Anmelde-ID + Schalter bleiben. Kein Netz.
 */

$tmp = sys_get_temp_dir() . '/mgd-suche-kit-' . bin2hex(random_bytes(4));
mkdir($tmp . '/templates_c', 0700, true);
define('PFAD_ROOT', $tmp . '/');
define('PFAD_COMPILEDIR', 'templates_c/');
define('URL_SHOP', 'https://demo.example.test');

$pluginDir = realpath(__DIR__ . '/..');

// Attrappe JTL Bootstrapper, nur damit Bootstrap::KIT_OPTIONS lesbar ist
if (!class_exists('JTL\\Plugin\\Bootstrapper')) {
    eval('namespace JTL\\Plugin; abstract class Bootstrapper {}');
}

// Autoload wie JTL (Plugin\ > plugins/)
spl_autoload_register(static function (string $class) use ($pluginDir): void {
    $prefix = 'Plugin\\MGDSucheJTLShop\\';
    if (str_starts_with($class, $prefix)) {
        $file = $pluginDir . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});

$fails = 0;
$count = 0;
function check(string $name, bool $cond): void
{
    global $fails, $count;
    $count++;
    echo ($cond ? 'OK   ' : 'FAIL ') . $name . "\n";
    if (!$cond) {
        $fails++;
    }
}

// Fake JTL-Plugin
$plugin = new class ($pluginDir) {
    public function __construct(private string $dir)
    {
    }

    public function getPaths(): object
    {
        return new class ($this->dir) {
            public function __construct(private string $dir)
            {
            }

            public function getBasePath(): string
            {
                return $this->dir . '/';
            }
        };
    }

    public function getMeta(): object
    {
        return new class {
            public function getVersion(): string
            {
                return '1.6.0';
            }
        };
    }
};

// alter Stand 1.5.0: Anmeldung aus, ID vorhanden
$old = $tmp . '/templates_c/mgd_suche_update';
mkdir($old, 0700, true);
$id = '0b6c2b6e-4f1a-4c4e-9d3a-2f6f0f6e1a11';
file_put_contents($old . '/install.json', json_encode(['install_id' => $id, 'report' => false]));
file_put_contents($old . '/state.json', json_encode(['enabled' => true]));

$opts = Plugin\MGDSucheJTLShop\Bootstrap::KIT_OPTIONS ?? null;
check('Bootstrap hat Kit-Optionen', $opts === ['dataFolder' => 'mgd_suche_update']);

$kit = Plugin\MGDSucheJTLShop\Kit\Platform\Jtl\Setup::forShop($plugin, $opts ?? []);
check('Baukasten startet', $kit !== null);
$data = json_decode((string) file_get_contents($old . '/install.json'), true);
check('alte Anmelde-ID bleibt', ($data['install_id'] ?? '') === $id);
check('Schalter Anmeldung bleibt aus', ($data['report'] ?? null) === false);
check('Update-Prüfung bleibt an', (json_decode((string) file_get_contents($old . '/state.json'), true)['enabled'] ?? null) === true);
check('kein zweiter Datenordner', !is_dir($tmp . '/templates_c/mgdsuchejtlshop_update'));
check('Config Produkt mgd-suche', Plugin\MGDSucheJTLShop\Kit\Platform\Jtl\Config::PRODUCT === 'mgd-suche');
check('Config Katalog-Kennung', Plugin\MGDSucheJTLShop\Kit\Platform\Jtl\Config::CATALOG_SLUG === 'plugin-suche-jtl');
// ausgeschaltet = kein Netz, darf nicht hängen
$t = microtime(true);
$kit?->ping(true);
check('Anmeldung aus: ping ohne Netz', microtime(true) - $t < 1.0);

// aufräumen (nur eigener Temp-Ordner)
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($it as $f) {
    $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
}
@rmdir($tmp);

echo "\nKitBridge: $count Prüfungen, $fails Fehler\n";
exit($fails === 0 ? 0 : 1);
