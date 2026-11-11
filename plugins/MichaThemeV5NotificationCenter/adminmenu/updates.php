<?php

declare(strict_types=1);

use JTL\Plugin\PluginInterface;
use Plugin\MichaThemeV5NotificationCenter\Update\UpdateBridge;

if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);
    echo 'Dieser Bereich ist nur in der JTL-Administration verfügbar.';

    return;
}

require_once dirname(__DIR__) . '/src/Update/UpdateBridge.php';

// Fehler im Tab darf die Admin-Seite nicht kippen
try {
    echo UpdateBridge::render($oPlugin, dirname(__DIR__));
} catch (\Throwable $e) {
    echo '<div class="alert alert-warning" role="alert">Dieser Bereich konnte gerade nicht geladen werden. Der Shop läuft normal weiter.</div>';
    try {
        \JTL\Shop::Container()->getLogService()->warning('MichaThemeV5NotificationCenter: ' . get_class($e) . ' in ' . basename($e->getFile()) . ':' . $e->getLine());
    } catch (\Throwable) {
        // kein Log möglich, egal
    }
}
