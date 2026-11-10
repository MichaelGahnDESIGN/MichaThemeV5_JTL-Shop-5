<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Admin;

use JTL\Shop;
use Plugin\MichaThemeV5\Support\{Perms, UpdateData};
use Plugin\MichaThemeV5\Update\Factory as UpdateFactory;

defined('PFAD_ROOT') || exit;

/**
 * Tab "Updates". Die ganze Logik steckt im MGD Update Client (src/Update, erzeugt, nicht von Hand ändern).
 * Hier nur Verdrahtung: Produkt, Version, Domain, Rechte, CSRF, Datenordner. Es wird nie installiert.
 */
final class UpdatesPage extends Page
{
    // Produkt laut Update-API v1 (Free)
    public const PRODUCT = 'michatheme-v5';
    public const EDITION = 'free';
    public const PLUGIN_NAME = 'MichaThemeV5';
    // Prüfsummen-Datei auf All-Inkl (einfacher Modus, bis die Schlüssel da sind)
    public const SIMPLE_URL = 'https://theme.michael-gahn.de/plugin-updates/michatheme-v5.json';

    public function tabKey(): string
    {
        return 'updates';
    }

    public function render(): string
    {
        $dir = UpdateData::dir();
        if (!UpdateData::ensure($dir)) {
            return '<div class="alert alert-warning" role="alert">Der Datenordner für Updates ist nicht beschreibbar (templates_c). Bitte Rechte prüfen.</div>';
        }
        $page = UpdateFactory::page(
            $this->basePath() . '/update/root-keys.json',
            $dir,
            self::PRODUCT,
            self::EDITION,
            self::PLUGIN_NAME,
            $this->installedVersion(),
            (string) (parse_url(Shop::getURL(), PHP_URL_HOST) ?: ''),
            defined('APPLICATION_VERSION') ? (string) APPLICATION_VERSION : '',
            static fn (): bool => Perms::post() && Perms::token(),
            static fn (): bool => Perms::updates(),
            // CSRF Feld + Tab, damit man nach dem Klick wieder im Tab Updates landet
            Perms::tokenInput() . '<input type="hidden" name="cPluginTab" value="' . $this->e($this->tabName()) . '">',
            null,
            ['info.xml'],
            self::SIMPLE_URL,
        );
        $res = $page->handle((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_POST);
        if ($res['deliver'] !== null && !$this->send($res['deliver'], $dir)) {
            $res['html'] = '<p class="alert alert-danger">Die Datei konnte nicht ausgeliefert werden.</p>' . $res['html'];
        }

        return $res['html'];
    }

    private function basePath(): string
    {
        return rtrim($this->plugin->getPaths()->getBasePath(), '/\\');
    }

    // Version aus dem Plugin-Objekt, Ersatz info.xml. Nie fest einkodiert
    private function installedVersion(): string
    {
        try {
            $meta = $this->plugin->getMeta();
            foreach (['getSemVer', 'getVersion'] as $m) {
                $v = method_exists($meta, $m) ? trim((string) $meta->$m()) : '';
                if (preg_match('/^\d+\.\d+\.\d+$/', $v) === 1) {
                    return $v;
                }
            }
        } catch (\Throwable) {
            // weiter mit info.xml
        }
        $xml = @simplexml_load_file($this->basePath() . '/info.xml');

        return $xml !== false ? trim((string) $xml->Version) : '';
    }

    // geprüftes ZIP ausliefern, nur Dateien aus dem Datenordner
    private function send(string $file, string $dir): bool
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
