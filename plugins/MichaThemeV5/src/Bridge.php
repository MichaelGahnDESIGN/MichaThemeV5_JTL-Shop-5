<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5;

use MichaThemeV5\Plugin\BridgeInterface;
use Plugin\MichaThemeV5\License\LicenseService;
use Plugin\MichaThemeV5\Settings\TemplateStore;

defined('PFAD_ROOT') || exit;

/**
 * Bridge: Laufzeit-Schnittstelle zwischen Plugin und Template (docs/PLUGIN-BRIDGE.md im Pro-Repo).
 * Das Template ruft sie nur auf, wenn die Klasse da ist (class_exists), sonst gilt sein alter Weg.
 * Jeder Fehler wird verschluckt: das Plugin darf den Shop nie stören.
 */
final class Bridge implements BridgeInterface
{
    // kleinste Template-Version, mit der das Plugin zusammenarbeitet
    public const TEMPLATE_MIN = '0.5.0';

    private static ?self $inst = null;

    public static function instance(): self
    {
        return self::$inst ??= new self();
    }

    public function option(string $id): mixed
    {
        try {
            return TemplateStore::forShop()->option($id);
        } catch (\Throwable) {
            return null;
        }
    }

    public function licenseState(): array
    {
        return LicenseService::forShop()->state();
    }

    public function licenseAllows(string $module): bool
    {
        return LicenseService::forShop()->allows($module);
    }

    public function pluginVersion(): string
    {
        // Version steht nur an einer Stelle (vom Generator geschrieben)
        $v = require __DIR__ . '/Generated/version.php';

        return is_array($v) && is_string($v['version'] ?? null) ? $v['version'] : '0';
    }

    /** Version des installierten Templates aus template.xml, null = nicht installiert */
    public static function templateVersion(): ?string
    {
        $xml = @file_get_contents(PFAD_ROOT . 'templates/MichaThemeV5/template.xml', false, null, 0, 65536);

        return is_string($xml) && preg_match('~<Version>\s*([0-9]+\.[0-9]+\.[0-9]+)\s*</Version>~', $xml, $m) === 1 ? $m[1] : null;
    }

    public function notice(string $key): ?string
    {
        if ($key === 'theme_outdated') {
            $v = self::templateVersion();

            return $v !== null && version_compare($v, self::TEMPLATE_MIN, '<')
                ? 'Das Template ist älter als vom Plugin erwartet. Bitte unter MichaThemeV5 › Info aktualisieren.' : null;
        }

        return null;
    }
}
