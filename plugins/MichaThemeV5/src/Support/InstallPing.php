<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Support;

use Plugin\MichaThemeV5\Install\InstallClient;
use Plugin\MichaThemeV5\Install\Platform\Jtl;

defined('PFAD_ROOT') || exit;

// Aktiv-Meldung Free-Lizenz, Client aus src/Install (erzeugt > nicht von Hand ändern). Nur Admin, nie im Frontend
// Fehler werden still geschluckt, Plugin läuft immer weiter
final class InstallPing
{
    // gleicher Name wie /plugin-updates/michatheme-v5.json
    public const PRODUCT = 'michatheme-v5';

    /** null = Datenordner kaputt oder Angaben ungültig, dann wird halt nix gemeldet */
    public static function client(?string $version = null, ?string $dir = null): ?InstallClient
    {
        try {
            $dir ??= UpdateData::dir();
            if (!UpdateData::ensure($dir)) {
                return null;
            }

            return Jtl::client($dir, self::PRODUCT, $version ?? self::version());
        } catch (\Throwable) {
            return null;
        }
    }

    // force = installiert / aktiviert / aktualisiert, sonst max 1x am Tag
    public static function ping(bool $force = false, ?string $version = null): void
    {
        try {
            self::client($version)?->ping($force);
        } catch (\Throwable) {
            // egal, nächster Admin-Aufruf
        }
    }

    // Version aus den mitgelieferten Dateien (passt auch direkt nach dem Update)
    public static function version(): string
    {
        try {
            $v = include __DIR__ . '/../Generated/version.php';

            return is_array($v) && is_string($v['version'] ?? null) ? $v['version'] : '';
        } catch (\Throwable) {
            return '';
        }
    }
}
