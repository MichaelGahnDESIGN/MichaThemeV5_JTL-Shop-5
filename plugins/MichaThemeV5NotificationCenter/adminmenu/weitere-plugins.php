<?php

declare(strict_types=1);

use JTL\Plugin\PluginInterface;
use Plugin\MichaThemeV5NotificationCenter\Kit\Platform\Jtl\Setup;

if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);
    echo 'Dieser Bereich ist nur in der JTL-Administration verfügbar.';

    return;
}

echo Setup::renderTab(Setup::forShop($oPlugin, array('legacyFolders' => array('mgd_notification_center_update'))), 'Weitere Plugins');
