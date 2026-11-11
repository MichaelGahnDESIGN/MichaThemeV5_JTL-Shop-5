<?php

declare(strict_types=1);

use JTL\Plugin\PluginInterface;
use Plugin\MGDSucheJTLShop\Update\UpdateBridge;

if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);
    echo 'Dieser Bereich ist nur in der JTL-Administration verfügbar.';

    return;
}

// Fehler im Tab darf die Admin-Seite nicht kippen
try {
    $bridgeFile = dirname(__DIR__) . '/src/Update/UpdateBridge.php';
    if (!is_file($bridgeFile)) {
        throw new \RuntimeException('Plugin-Datei fehlt: UpdateBridge.php');
    }
    require_once $bridgeFile;
    echo UpdateBridge::render($oPlugin, dirname(__DIR__));
} catch (\Throwable $e) {
    echo '<div class="alert alert-warning" role="alert">Dieser Bereich konnte gerade nicht geladen werden. Der Shop läuft normal weiter.</div>';
    try {
        \JTL\Shop::Container()->getLogService()->warning('MGDSucheJTLShop: ' . get_class($e) . ' in ' . basename($e->getFile()) . ':' . $e->getLine());
    } catch (\Throwable) {
        // kein Log möglich, egal
    }
}
