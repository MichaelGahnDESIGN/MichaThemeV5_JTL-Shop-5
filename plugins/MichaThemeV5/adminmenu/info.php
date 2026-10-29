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

try {
    echo (new \Plugin\MichaThemeV5\Admin\InfoPage($oPlugin))->render();
} catch (\Throwable $e) {
    // Fehler im Tab darf die Admin-Seite nicht kippen
    echo '<div class="alert alert-warning" role="alert">Dieser Bereich konnte gerade nicht geladen werden. Der Shop läuft normal weiter.</div>';
    try {
        \JTL\Shop::Container()->getLogService()->warning('MichaThemeV5: ' . get_class($e) . ' in ' . basename($e->getFile()) . ':' . $e->getLine());
    } catch (\Throwable) {
        // kein Log möglich, egal
    }
}
