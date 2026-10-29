<?php

declare(strict_types=1);

defined('PFAD_ROOT') || exit;

// Kernklassen (Namespace MichaThemeV5\Plugin), Reihenfolge wichtig. Wer zuerst da ist (z. B. das Template), gewinnt
foreach (['BridgeInterface', 'Sanitizer', 'FeedNormalizer', 'CatalogFeed', 'LicenseTexts', 'ModuleLoader', 'FieldRenderer', 'PageRenderer', 'ColorContrast', 'ColorOverrides', 'Presets', 'LicenseClient'] as $mtCore) {
    $mtName = 'MichaThemeV5\\Plugin\\' . $mtCore;
    if (!class_exists($mtName, false) && !interface_exists($mtName, false)) {
        require_once __DIR__ . '/Generated/' . $mtCore . '.php';
    }
}
unset($mtCore, $mtName);

// eigene Klassen: Plugin\MichaThemeV5\Foo\Bar > src/Foo/Bar.php
spl_autoload_register(static function (string $class): void {
    $prefix = 'Plugin\\MichaThemeV5\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $rel = substr($class, strlen($prefix));
    if (preg_match('/^[A-Za-z0-9\\\\]+$/', $rel) !== 1) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
