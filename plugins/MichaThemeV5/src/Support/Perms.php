<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Support;

use JTL\Backend\Permissions;
use JTL\Helpers\Form;
use JTL\Shop;

defined('PFAD_ROOT') || exit;

/** Rechte und Token im JTL-Admin. Serverseitig, ausblenden im Menü ersetzt das nie. */
final class Perms
{
    public static function post(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    public static function token(): bool
    {
        return Form::validateToken();
    }

    public static function tokenInput(): string
    {
        return (string) Form::getTokenInput();
    }

    public static function can(string $permission): bool
    {
        try {
            return Shop::Container()->getAdminAccount()->permission($permission);
        } catch (\Throwable) {
            return false;
        }
    }

    /** Template-Einstellungen ändern: gleiches Recht wie die Seite Darstellung > Template */
    public static function settings(): bool
    {
        return self::can(Permissions::DISPLAY_TEMPLATE_VIEW);
    }

    /** Lizenzcode, Backup einspielen */
    public static function license(): bool
    {
        return self::can(Permissions::PLUGIN_ADMIN_VIEW);
    }

    /** Dateien schreiben (Template einrichten): nur Hauptadministrator und mit Template-Recht */
    public static function files(): bool
    {
        try {
            $account = Shop::Container()->getAdminAccount()->account();

            return $account !== false && (int) $account->oGroup->kAdminlogingruppe === \ADMINGROUP && self::settings();
        } catch (\Throwable) {
            return false;
        }
    }
}
