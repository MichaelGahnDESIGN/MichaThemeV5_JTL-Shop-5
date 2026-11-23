<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Support;

use Plugin\MichaThemeV5\Kit\Platform\Jtl\{JtlKit, Setup};

defined('PFAD_ROOT') || exit;

// Plugin-Baukasten (Kit/ > erzeugt, nicht von Hand ändern). Werte die vom Standard abweichen sitzen nur hier
final class KitSetup
{
    /**
     * dataFolder = alter Ordner, sonst gehen Install-ID + Schalter verloren
     * repo leer, weil das Repo auch MGD Suche + Notification Center enthält (sonst blendet der Baukasten die aus)
     *
     * @return array<string,mixed>
     */
    public static function options(): array
    {
        return ['dataFolder' => UpdateData::FOLDER, 'repo' => ''];
    }

    // null = irgendwas fehlt, dann passiert einfach nix
    public static function forShop(object $plugin): ?JtlKit
    {
        try {
            return Setup::forShop($plugin, self::options());
        } catch (\Throwable) {
            return null;
        }
    }
}
