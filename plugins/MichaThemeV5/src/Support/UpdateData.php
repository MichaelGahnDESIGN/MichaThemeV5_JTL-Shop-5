<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Support;

defined('PFAD_ROOT') || exit;

// Datenordner Updates (Zustand + geprüfte ZIP). Liegt in templates_c, nicht im Plugin-Ordner (der wird bei Updates ersetzt)
final class UpdateData
{
    public const FOLDER = 'mgd_update_michathemev5';

    private const HTACCESS = "# Datenordner Updates, kein Webzugriff\nOptions -Indexes\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";

    public static function dir(?string $base = null): string
    {
        $base ??= defined('PFAD_COMPILEDIR') ? PFAD_ROOT . PFAD_COMPILEDIR : sys_get_temp_dir();

        return rtrim($base, '/\\') . '/' . self::FOLDER;
    }

    // Ordner anlegen (nur Besitzer) + .htaccess Deny + leere index.html. false = nicht möglich
    public static function ensure(string $dir): bool
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
}
