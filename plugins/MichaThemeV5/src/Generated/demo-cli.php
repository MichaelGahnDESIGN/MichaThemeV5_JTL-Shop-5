<?php

declare(strict_types=1);

/**
 * Kommandozeile für die Demo-Inhalte. Aufruf im Shop-Hauptordner:
 *   php plugins/MichaThemeV5/src/Generated/demo-cli.php import|status|remove [--yes]
 * Läuft nur in der CLI und lädt dafür den JTL-Shop (includes/globalinclude.php).
 */
if (PHP_SAPI !== 'cli') {
    exit;
}

$root = dirname(__DIR__, 4);
chdir($root);
const CLI_BATCHRUN = true;
require_once $root . '/includes/globalinclude.php';
require_once __DIR__ . '/DemoImport.php';
require_once __DIR__ . '/DemoStoreJtl.php';

use MichaThemeV5\Plugin\{DemoCli, DemoImporter, DemoStoreJtl};

$container = JTL\Shop::Container();
$imp = new DemoImporter(new DemoStoreJtl($container->getDB(), $root, $container->getCache()), __DIR__ . '/demo-data');
exit(DemoCli::run(array_slice($argv, 1), $imp, static fn (string $l) => fwrite(STDOUT, $l . "\n"), static function (string $q): bool {
    fwrite(STDOUT, $q);

    return in_array(strtolower(trim((string) fgets(STDIN))), ['ja', 'j', 'yes', 'y'], true);
}));
