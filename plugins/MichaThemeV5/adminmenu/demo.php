<?php

declare(strict_types=1);

use JTL\Plugin\PluginInterface;

// nur im JTL-Admin (wird von der Plugin-Seite eingebunden), sonst nichts ausgeben
if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);
    echo 'Dieser Bereich ist nur in der JTL-Administration verfügbar.';
    return;
}

require_once dirname(__DIR__) . '/src/autoload.php';
require_once dirname(__DIR__) . '/src/Generated/DemoImport.php';
require_once dirname(__DIR__) . '/src/Generated/DemoStoreJtl.php';
require_once dirname(__DIR__) . '/src/Generated/DemoPage.php';

try {
    $container = \JTL\Shop::Container();
    $importer = new \MichaThemeV5\Plugin\DemoImporter(
        new \MichaThemeV5\Plugin\DemoStoreJtl($container->getDB(), PFAD_ROOT, $container->getCache()),
        dirname(__DIR__) . '/src/Generated/demo-data'
    );
    echo \MichaThemeV5\Plugin\DemoPage::render($importer, $_SERVER['REQUEST_METHOD'] ?? 'GET', $_POST);
} catch (\Throwable $e) {
    // Fehler im Tab darf die Admin-Seite nicht kippen
    echo '<div class="alert alert-warning" role="alert">Dieser Bereich konnte gerade nicht geladen werden. Der Shop läuft normal weiter.</div>';
    try {
        \JTL\Shop::Container()->getLogService()->warning('MichaThemeV5: ' . get_class($e) . ' in ' . basename($e->getFile()) . ':' . $e->getLine());
    } catch (\Throwable) {
        // kein Log möglich, egal
    }
}
