<?php

declare(strict_types=1);

namespace Plugin\MGDSucheJTLShop\Update;

use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\MGDSucheJTLShop\UpdateClient\Factory;

defined('PFAD_ROOT') || exit;

/**
 * Verdrahtung MGD Update Client (UpdateClient/ ist erzeugt, nicht von Hand ändern).
 * Hier nur: Produkt, Version, Domain, Rechte, CSRF, Datenordner. Es wird nie installiert.
 */
final class UpdateBridge
{
    // Produkt laut Update-API v1 (Free)
    public const PRODUCT = 'mgd-suche';
    public const EDITION = 'free';
    public const PLUGIN_NAME = 'MGD Suche';
    public const FOLDER = 'mgd_suche_update';
    // Prüfsummen-Datei auf All-Inkl > einfacher Modus ohne Schlüssel
    public const SIMPLE_URL = 'https://theme.michael-gahn.de/plugin-updates/mgd-suche.json';

    private const HTACCESS = "# Datenordner Updates, kein Webzugriff\nOptions -Indexes\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";

    // CSS gekapselt, keine externen Schriften oder Skripte
    private const STYLE = '.mgd-updates-shell{--mgd-ink:#17312f;--mgd-muted:#526460;--mgd-line:#dbe3df;max-width:1240px;margin:24px 0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:var(--mgd-ink)}.mgd-updates-shell *{box-sizing:border-box}.mgd-updates-shell__hero{padding:32px;border-radius:20px;background:radial-gradient(circle at 88% 28%,rgba(230,193,123,.28),transparent 26%),linear-gradient(130deg,#17312f,#0e2926);color:#fff}.mgd-updates-shell__eyebrow{margin:0 0 10px;font-size:12px;letter-spacing:.14em;font-weight:800;text-transform:uppercase;color:#e8cb91}.mgd-updates-shell__hero h1{max-width:620px;margin:0;font-size:clamp(28px,4vw,47px);line-height:1.05;color:#fff}.mgd-updates-shell__hero p:last-child{max-width:620px;margin:16px 0 0;font-size:16px;color:#e0e9e7}.mgd-updates-shell__card{max-width:760px;margin:22px 0 0;padding:26px;background:#fff;border:1px solid var(--mgd-line);border-radius:16px;box-shadow:0 5px 16px rgba(18,49,46,.08);font-size:15px;line-height:1.58}.mgd-updates-shell__card h3{display:none}.mgd-updates-shell__card p{margin:0 0 14px}.mgd-updates-shell__card label{display:flex;align-items:flex-start;gap:10px;margin:0 0 10px;font-weight:650;cursor:pointer}.mgd-updates-shell__card input[type=checkbox]{width:18px;height:18px;margin:2px 0 0;accent-color:#17312f}.mgd-updates-shell__card .text-muted{color:var(--mgd-muted)!important;font-size:13px}.mgd-updates-shell__card hr{margin:22px 0;border:0;border-top:1px solid var(--mgd-line)}.mgd-updates-shell__card .btn{min-height:40px;padding:8px 16px;border-radius:8px;font-size:14px;font-weight:700;box-shadow:none}.mgd-updates-shell__card .btn-primary{border:1px solid #17312f;background:#17312f;color:#fff}.mgd-updates-shell__card .btn-default{border:1px solid #17312f;background:#fff;color:#17312f}.mgd-updates-shell__card .btn:hover{filter:brightness(1.15)}.mgd-updates-shell__card .btn:focus-visible{outline:3px solid #e8cb91;outline-offset:2px}.mgd-updates-shell__card .alert{margin:0 0 18px;padding:13px 16px;border:0;border-left:4px solid #315d58;border-radius:9px;background:#eef3ef;color:#17312f}.mgd-updates-shell__card .alert-success{border-left-color:#257449;background:#edf7ee;color:#1d4f2d}.mgd-updates-shell__card .alert-warning{border-left-color:#b17618;background:#fff8e8;color:#5d450d}.mgd-updates-shell__card .alert-danger{border-left-color:#a3352b;background:#fdeeec;color:#6d1f18}.mgd-updates-shell__card ol{margin:18px 0 0;padding-left:20px;color:#42534e}.mgd-updates-shell__card .mt-2{margin-top:10px}.mgd-updates-shell__card .mt-3{margin-top:18px}.mgd-updates-shell__privacy{margin:22px 0 0;color:var(--mgd-muted);font-size:13px}@media (max-width:600px){.mgd-updates-shell__hero{padding:25px}.mgd-updates-shell__card{padding:20px}.mgd-updates-shell__card .btn{width:100%}}';

    // eigener Autoloader für UpdateClient/ (Plugin hat sonst nur require_once)
    public static function registerAutoload(string $pluginDir): void
    {
        spl_autoload_register(static function (string $class) use ($pluginDir): void {
            $prefix = 'Plugin\\MGDSucheJTLShop\\UpdateClient\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }
            $file = rtrim($pluginDir, '/\\') . '/UpdateClient/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        });
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
        );
        $res = $page->handle((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_POST);
        if ($res['deliver'] !== null && !self::send($res['deliver'], $dir)) {
            $res['html'] = '<p class="alert alert-danger">Die Datei konnte nicht ausgeliefert werden.</p>' . $res['html'];
        }

        return self::decorate($res['html']);
    }

    // MGD Look für den Updates Tab, gleiche Optik wie Plugin-Empfehlungen
    public static function decorate(string $html): string
    {
        return '<style>' . self::STYLE . '</style>'
            . '<section class="mgd-updates-shell" aria-labelledby="mgd-updates-title">'
            . '<header class="mgd-updates-shell__hero">'
            . '<p class="mgd-updates-shell__eyebrow">Michael Gahn DESIGN · ' . htmlspecialchars(self::PLUGIN_NAME, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<h1 id="mgd-updates-title">Immer auf dem neuesten Stand.</h1>'
            . '<p>Hier prüfen Sie, ob es eine neue Version gibt. Installiert wird nie automatisch, sondern immer von Ihnen über die JTL-Pluginverwaltung.</p>'
            . '</header>'
            . '<div class="mgd-updates-shell__card">' . $html . '</div>'
            . '<p class="mgd-updates-shell__privacy">Beim Prüfen werden keine Besucher-, Kunden- oder Bestelldaten gesendet. Jedes Paket wird vor dem Speichern per SHA-256 geprüft.</p>'
            . '</section>';
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
