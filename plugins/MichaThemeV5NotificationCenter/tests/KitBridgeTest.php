<?php

declare(strict_types=1);

// Baukasten richtig verdrahtet? php plugins/MichaThemeV5NotificationCenter/tests/KitBridgeTest.php (ohne JTL)
$root = dirname(__DIR__);
$boot = (string) file_get_contents($root . '/Bootstrap.php');
$info = (string) file_get_contents($root . '/info.xml');
$cfg = (string) file_get_contents($root . '/Kit/Platform/Jtl/Config.php');
$fails = 0;
$count = 0;
function ok(string $n, bool $c): void
{
    global $fails, $count;
    $count++;
    echo ($c ? 'OK   ' : 'FAIL ') . $n . "\n";
    if (!$c) {
        $fails++;
    }
}

ok('Produktname mgd-notification-center', str_contains($cfg, "'mgd-notification-center'"));
ok('Katalog-Kennung eigene', str_contains($cfg, 'plugin-notification-center-jtl'));
ok('Version 2.1.4 in info.xml', str_contains($info, '<Version>2.1.4</Version>'));
ok('Tabs Übersicht und Weitere Plugins', str_contains($info, '<Name>Übersicht</Name>') && str_contains($info, '<Name>Weitere Plugins</Name>'));
ok('alter Updates-Tab weg', !str_contains($info, 'updates.php') && !is_file($root . '/adminmenu/updates.php'));
ok('alter Update-Code weg', !is_dir($root . '/UpdateClient') && !is_dir($root . '/src/Install') && !is_dir($root . '/src/Update'));
ok('Bootstrap nutzt Baukasten', str_contains($boot, 'Setup::forShop(') && str_contains($boot, 'Setup::isBackend()') && str_contains($boot, 'ping(true)'));
ok('alter Datenordner wird übernommen', str_contains($boot, "'mgd_notification_center_update'"));
ok('Abmelden beim Deinstallieren', preg_match('/function\s+uninstalled\s*\([^)]*\)[^{]*\{.*?->uninstall\(/s', $boot) === 1);
ok('Version bei updated() neu', str_contains($boot, "'version' => (string) \$newVersion"));
ok('Icons da', is_file($root . '/assets/icon-128x128.png') && is_file($root . '/assets/icon-256x256.png'));
foreach (['uebersicht.php' => 'Übersicht', 'weitere-plugins.php' => 'Weitere Plugins'] as $f => $tab) {
    ok("adminmenu/$f ruft renderTab('$tab')", str_contains((string) file_get_contents($root . '/adminmenu/' . $f), "renderTab(") && str_contains((string) file_get_contents($root . '/adminmenu/' . $f), "'$tab'"));
}

echo "\n$count Prüfungen, $fails Fehler\n";
exit($fails === 0 ? 0 : 1);
