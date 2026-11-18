<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5NotificationCenter\Update;

use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\MichaThemeV5NotificationCenter\Install\InstallClient;
use Plugin\MichaThemeV5NotificationCenter\Install\Platform\Jtl;
use Plugin\MichaThemeV5NotificationCenter\Install\Settings as InstallSettings;
use Plugin\MichaThemeV5NotificationCenter\UpdateClient\Admin\InstallReporting;
use Plugin\MichaThemeV5NotificationCenter\UpdateClient\Factory;

defined('PFAD_ROOT') || exit;

/**
 * Verdrahtung MGD Update Client (UpdateClient/ ist erzeugt, nicht von Hand ändern).
 * Hier nur: Produkt, Version, Domain, Rechte, CSRF, Datenordner. Es wird nie installiert.
 */
final class UpdateBridge
{
    // Produkt laut Update-API v1 (Free)
    public const PRODUCT = 'mgd-notification-center';
    public const EDITION = 'free';
    public const PLUGIN_NAME = 'MGD Notification Center';
    public const FOLDER = 'mgd_notification_center_update';
    // Prüfsummen-Datei auf All-Inkl > einfacher Modus ohne Schlüssel
    public const SIMPLE_URL = 'https://theme.michael-gahn.de/plugin-updates/mgd-notification-center.json';

    private const HTACCESS = "# Datenordner Updates, kein Webzugriff\nOptions -Indexes\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";

    // eigener Autoloader für UpdateClient/ + src/Install/ (Plugin hat sonst nur require_once)
    public static function registerAutoload(string $pluginDir): void
    {
        static $done = [];
        if (isset($done[$pluginDir])) {
            return;
        }
        $done[$pluginDir] = true;
        $map = [
            'Plugin\\MichaThemeV5NotificationCenter\\UpdateClient\\' => '/UpdateClient/',
            'Plugin\\MichaThemeV5NotificationCenter\\Install\\' => '/src/Install/',
        ];
        spl_autoload_register(static function (string $class) use ($pluginDir, $map): void {
            foreach ($map as $prefix => $sub) {
                if (!str_starts_with($class, $prefix)) {
                    continue;
                }
                $file = rtrim($pluginDir, '/\\') . $sub . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
                if (is_file($file)) {
                    require_once $file;
                }

                return;
            }
        });
    }

    // Free-Lizenz Anmeldung, null = aus/ungültig. Nie Exception
    public static function installClient(PluginInterface $plugin, string $pluginDir): ?InstallClient
    {
        try {
            self::registerAutoload($pluginDir);
            $dir = self::dataDir();
            if (!self::ensureDir($dir)) {
                return null;
            }

            return Jtl::client($dir, self::PRODUCT, self::installedVersion($plugin, $pluginDir));
        } catch (\Throwable) {
            return null;
        }
    }

    // einmal melden (installed/enabled/updated) oder max 1x am Tag
    public static function ping(PluginInterface $plugin, string $pluginDir, bool $force = false): void
    {
        try {
            self::installClient($plugin, $pluginDir)?->ping($force);
        } catch (\Throwable) {
            // Plattform weg ist egal, Plugin läuft weiter
        }
    }

    // Datenordner in templates_c (Plugin-Ordner wird bei Updates ersetzt)
    public static function dataDir(?string $base = null): string
    {
        $base ??= defined('PFAD_COMPILEDIR') ? PFAD_ROOT . PFAD_COMPILEDIR : sys_get_temp_dir();

        return rtrim($base, '/\\') . '/' . self::FOLDER;
    }

    // Ordner anlegen (nur Besitzer) + .htaccess Deny + leere index.html
    public static function ensureDir(string $dir): bool
    {
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            return false;
        }
        $ok = true;
        foreach (['.htaccess' => self::HTACCESS, 'index.html' => ''] as $name => $content) {
            $f = $dir . '/' . $name;
            if (!is_file($f) && @file_put_contents($f, $content) === false) {
                $ok = false;
            }
        }

        return $ok;
    }

    // Version aus dem Plugin-Objekt, Ersatz info.xml. Nie fest im Code
    public static function installedVersion(PluginInterface $plugin, string $pluginDir): string
    {
        try {
            $v = trim((string) $plugin->getMeta()->getVersion());
            if (preg_match('/^\d+\.\d+\.\d+$/', $v) === 1) {
                return $v;
            }
        } catch (\Throwable) {
            // weiter mit info.xml
        }
        $xml = @simplexml_load_file(rtrim($pluginDir, '/\\') . '/info.xml');

        return $xml !== false ? trim((string) $xml->Version) : '';
    }

    public static function render(PluginInterface $plugin, string $pluginDir): string
    {
        self::registerAutoload($pluginDir);
        $dir = self::dataDir();
        if (!self::ensureDir($dir)) {
            return '<div class="alert alert-warning" role="alert">Der Datenordner für Updates ist nicht beschreibbar (templates_c). Bitte Rechte prüfen.</div>';
        }
        $token = static function (): bool {
            try {
                return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST' && \JTL\Helpers\Form::validateToken();
            } catch (\Throwable) {
                return false;
            }
        };
        $right = static function (): bool {
            try {
                return (bool) Shop::Container()->getAdminAccount()->permission('PLUGIN_ADMIN_VIEW');
            } catch (\Throwable) {
                return false;
            }
        };
        // Schalter Free-Lizenz auf der Update-Seite
        $ic = self::installClient($plugin, $pluginDir);
        $install = $ic === null ? null : new InstallReporting(
            static fn () => $ic->ping(),
            static fn (): bool => $ic->isEnabled(),
            static fn (bool $an) => $ic->setEnabled($an),
            static fn (): string => $ic->status(),
            InstallSettings::HINT,
        );
        $page = Factory::page(
            rtrim($pluginDir, '/\\') . '/update/root-keys.json',
            $dir,
            self::PRODUCT,
            self::EDITION,
            self::PLUGIN_NAME,
            self::installedVersion($plugin, $pluginDir),
            (string) (parse_url(Shop::getURL(), PHP_URL_HOST) ?: ''),
            defined('APPLICATION_VERSION') ? (string) APPLICATION_VERSION : '',
            $token,
            $right,
            \JTL\Helpers\Form::getTokenInput(),
            null,
            ['info.xml'],
            self::SIMPLE_URL,
            $install,
        );
        $res = $page->handle((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_POST);
        if ($res['deliver'] !== null && !self::send($res['deliver'], $dir)) {
            $res['html'] = '<p class="alert alert-danger">Die Datei konnte nicht ausgeliefert werden.</p>' . $res['html'];
        }

        return $res['html'];
    }

    // geprüftes ZIP ausliefern, nur Dateien aus dem Datenordner
    public static function send(string $file, string $dir): bool
    {
        $real = realpath($file);
        $base = realpath($dir);
        if ($real === false || $base === false || !str_starts_with($real, $base . DIRECTORY_SEPARATOR) || !is_file($real) || headers_sent()) {
            return false;
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($real));
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . (string) filesize($real));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        readfile($real);
        exit;
    }
}
