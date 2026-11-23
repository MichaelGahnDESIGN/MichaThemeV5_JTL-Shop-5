<?php

declare(strict_types=1);

use JTL\Plugin\PluginInterface;
use Plugin\MGDSucheJTLShop\Bootstrap;
use Plugin\MGDSucheJTLShop\Kit\Platform\Jtl\Setup;

// Baukasten Tab Übersicht
if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);
    return;
}
echo Setup::renderTab(Setup::forShop($oPlugin, Bootstrap::KIT_OPTIONS), 'Übersicht');
