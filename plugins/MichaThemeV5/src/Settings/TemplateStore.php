<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Settings;

use JTL\DB\DbInterface;
use JTL\Shop;
use JTL\Template\Config;
use MichaThemeV5\Plugin\{Presets, Sanitizer};
use Plugin\MichaThemeV5\Support\PluginData;

defined('PFAD_ROOT') || exit;

/**
 * Liest und schreibt die Template-Einstellungen. Gleicher Speicher wie das Template (ttemplateeinstellungen,
 * Abschnitt mt_<gruppe>, Name mt_<id>), also keine Datenkopie. Kein Schlüssel wird umbenannt oder gelöscht.
 */
final class TemplateStore
{
    public const TEMPLATE = 'MichaThemeV5';
    private const PALETTE = 'mt_palette';
    private const BACKUP = 'mt_settings_backup';

    private ?array $schema = null;

    public function __construct(private readonly DbInterface $db, private readonly ?PluginData $data = null)
    {
    }

    public static function forShop(): self
    {
        $db = Shop::Container()->getDB();

        return new self($db, new PluginData($db));
    }

    /** @return array<string,mixed> */
    public function schema(): array
    {
        if ($this->schema === null) {
            $raw = (string) @file_get_contents(dirname(__DIR__) . '/Generated/mt-admin-schema.json');
            $d = json_decode($raw, true);
            $this->schema = is_array($d) && ($d['schemaVersion'] ?? 0) === 1 ? $d : ['schemaVersion' => 1, 'groups' => [], 'options' => []];
        }

        return $this->schema;
    }

    /** @return array<string,array<string,mixed>> id => Option */
    public function options(): array
    {
        $out = [];
        foreach ($this->schema()['options'] as $o) {
            $out[$o['id']] = $o;
        }

        return $out;
    }

    private function config(): Config
    {
        return new Config(self::TEMPLATE, $this->db);
    }

    /** Ist das Template im Ordner templates/ vorhanden? Dann gibt es auch Einstellungen. */
    public static function templateInstalled(): bool
    {
        return is_file(PFAD_ROOT . 'templates/' . self::TEMPLATE . '/template.xml');
    }

    /** Ist MichaThemeV5 das aktive Template des Shops? */
    public function templateActive(): bool
    {
        $row = $this->db->select('ttemplate', 'cTemplate', self::TEMPLATE, 'eTyp', 'standard');

        return $row !== null;
    }

    /** @return array<string,mixed> id => aktueller (geprüfter) Wert, Schalter als bool */
    public function values(): array
    {
        $saved = $this->config()->loadConfigFromDB();
        $v = [];
        foreach ($this->options() as $id => $o) {
            $raw = $saved[$o['section']][$o['key']] ?? null;
            $v[$id] = Sanitizer::value($o, $raw === null ? $o['default'] : $raw);
        }

        return $v;
    }

    /** Wert so, wie ihn das Template in der Datenbank erwartet (Schalter Y/N, Zahlen als Text). */
    private static function dbValue(array $o, mixed $value): string
    {
        if ($o['type'] === 'toggle') {
            return $value ? 'Y' : 'N';
        }

        return (string) $value;
    }

    /**
     * Speichert geänderte Werte. Nur Schlüssel, deren Wert sich wirklich ändert, werden geschrieben.
     *
     * @param array<string,mixed> $input id => roher Wert
     * @return array{changed:list<string>,adjusted:list<string>}
     */
    public function save(array $input): array
    {
        $opts = $this->options();
        $res = Sanitizer::form($opts, $input);
        $current = $this->values();
        $config = $this->config();
        $changed = [];
        $adjusted = [];
        $backedUp = false;
        foreach ($res['values'] as $id => $new) {
            $o = $opts[$id];
            $raw = $input[$id];
            // Ungültige Farbe wurde vom Prüfer geleert: dem Nutzer sagen
            if (is_string($raw) && trim($raw) !== '' && $o['type'] === 'color' && $new === '') {
                $adjusted[] = $id;
            }
            if ($new === $current[$id]) {
                continue;
            }
            if (!$backedUp) {
                $this->backup();
                $backedUp = true;
            }
            $config->updateConfigInDB($o['section'], $o['key'], self::dbValue($o, $new));
            $changed[] = $id;
        }
        if ($changed) {
            $this->flushCaches();
        }

        return ['changed' => $changed, 'adjusted' => $adjusted];
    }

    /** JTL-Cache leeren, sonst sieht der Shop alte Werte (wie die Seite Darstellung > Template) */
    public function flushCaches(): void
    {
        try {
            Shop::Container()->getCache()->flushTags([\CACHING_GROUP_OPTION, \CACHING_GROUP_TEMPLATE]);
        } catch (\Throwable) {
            // Cache-Fehler darf das Speichern nicht kippen
        }
    }

    /** @return list<array<string,mixed>> */
    public function presets(): array
    {
        return $this->schema()['presets'] ?? [];
    }

    /** Preset anwenden. Gibt die Zahl der geänderten Werte zurück, -1 = unbekanntes Preset. */
    public function applyPreset(string $id): int
    {
        foreach ($this->presets() as $p) {
            if ($p['id'] === $id) {
                return count($this->save(Presets::values($p, $this->options()))['changed']);
            }
        }

        return -1;
    }

    /** Sicherung aller gespeicherten mt-Werte (Plugin-Daten, nicht Teil der Template-Einstellungen). */
    public function backup(): void
    {
        $saved = $this->config()->loadConfigFromDB();
        $vals = [];
        foreach ($this->options() as $o) {
            if (isset($saved[$o['section']][$o['key']])) {
                $vals[$o['key']] = $saved[$o['section']][$o['key']];
            }
        }
        $this->data?->set(self::BACKUP, (string) json_encode(['at' => time(), 'values' => $vals]));
    }

    /** @return array{at:int,values:array<string,string>}|null */
    public function backupInfo(): ?array
    {
        $raw = $this->data?->get(self::BACKUP);
        $d = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($d) && is_int($d['at'] ?? null) && is_array($d['values'] ?? null) ? $d : null;
    }

    /** Spielt die Sicherung zurück (jeder Wert läuft erneut durch die Prüfung). Gibt die Zahl der gesetzten Werte zurück. */
    public function restore(): int
    {
        $b = $this->backupInfo();
        if ($b === null) {
            return 0;
        }
        $config = $this->config();
        $n = 0;
        foreach ($this->options() as $o) {
            if (!array_key_exists($o['key'], $b['values'])) {
                continue;
            }
            $raw = $b['values'][$o['key']];
            $clean = Sanitizer::value($o, $raw);
            $config->updateConfigInDB($o['section'], $o['key'], self::dbValue($o, $clean));
            $n++;
        }
        if ($n) {
            $this->flushCaches();
        }

        return $n;
    }

    /** Palette (höchstens 12 Farben #rrggbb), gilt für den ganzen Shop. */
    public function palette(): array
    {
        $raw = $this->data?->get(self::PALETTE);

        return self::cleanPalette(is_string($raw) ? json_decode($raw, true) : []);
    }

    public static function cleanPalette(mixed $list): array
    {
        $out = [];
        foreach (is_array($list) ? $list : [] as $c) {
            if (is_string($c) && preg_match('/^#[0-9a-fA-F]{6}$/', $c) === 1 && !in_array(strtolower($c), $out, true) && count($out) < 12) {
                $out[] = strtolower($c);
            }
        }

        return $out;
    }

    public function savePalette(mixed $raw): void
    {
        $list = is_string($raw) ? json_decode($raw, true) : null;
        $this->data?->set(self::PALETTE, (string) json_encode(self::cleanPalette($list)));
    }

    /** Einzelner Wert für die Bridge (null = unbekannt, das Template nimmt seinen eigenen Weg). */
    public function option(string $id): mixed
    {
        $o = $this->options()[$id] ?? null;
        if ($o === null) {
            return null;
        }
        $saved = $this->config()->loadConfigFromDB();
        $raw = $saved[$o['section']][$o['key']] ?? null;

        return Sanitizer::value($o, $raw === null ? $o['default'] : $raw);
    }
}
