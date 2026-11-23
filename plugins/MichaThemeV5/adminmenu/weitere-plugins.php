<?php

declare(strict_types=1);

use JTL\Plugin\PluginInterface;
use Plugin\MichaThemeV5\Kit\Platform\Jtl\Setup;
use Plugin\MichaThemeV5\Support\KitSetup;

if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);
    return;
}

require_once dirname(__DIR__) . '/src/autoload.php';

// Baukasten Tab, Datenordner kommt aus KitSetup
try {
    echo Setup::renderTab(KitSetup::forShop($oPlugin), 'Weitere Plugins');
} catch (\Throwable) {
    // Fehler im Tab darf die Admin-Seite nicht kippen
    echo '<div class="alert alert-warning" role="alert">Dieser Bereich konnte gerade nicht geladen werden. Der Shop läuft normal weiter.</div>';
}
