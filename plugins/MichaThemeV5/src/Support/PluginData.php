<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Support;

use JTL\DB\DbInterface;

defined('PFAD_ROOT') || exit;

/**
 * Eigene Daten des Plugins (Palette, Sicherung, Merker) in tplugineinstellungen.
 * Die Zeilen gehören zum Plugin: JTL löscht sie beim Deinstallieren und nimmt sie bei Updates mit.
 * Die Template-Einstellungen selbst liegen NICHT hier (die bleiben in ttemplateeinstellungen).
 */
final class PluginData
{
    public const PLUGIN_ID = 'MichaThemeV5';
    private const NAME = '/^mt_[a-z_]{1,40}$/';

    private ?int $pluginKey = null;

    public function __construct(private readonly DbInterface $db, ?int $pluginKey = null)
    {
        $this->pluginKey = $pluginKey;
    }

    private function key(): ?int
    {
        if ($this->pluginKey === null) {
            $row = $this->db->select('tplugin', 'cPluginID', self::PLUGIN_ID);
            $this->pluginKey = $row !== null && isset($row->kPlugin) ? (int) $row->kPlugin : 0;
        }

        return $this->pluginKey > 0 ? $this->pluginKey : null;
    }

    public function get(string $name): ?string
    {
        $key = $this->key();
        if ($key === null || preg_match(self::NAME, $name) !== 1) {
            return null;
        }
        $row = $this->db->select('tplugineinstellungen', 'kPlugin', $key, 'cName', $name);

        return $row !== null && isset($row->cWert) ? (string) $row->cWert : null;
    }

    public function set(string $name, string $value): bool
    {
        $key = $this->key();
        if ($key === null || preg_match(self::NAME, $name) !== 1) {
            return false;
        }
        $this->db->delete('tplugineinstellungen', ['kPlugin', 'cName'], [$key, $name]);
        $row = new \stdClass();
        $row->kPlugin = $key;
        $row->cName = $name;
        $row->cWert = $value;

        return $this->db->insert('tplugineinstellungen', $row) > 0;
    }

    public function delete(string $name): void
    {
        $key = $this->key();
        if ($key !== null && preg_match(self::NAME, $name) === 1) {
            $this->db->delete('tplugineinstellungen', ['kPlugin', 'cName'], [$key, $name]);
        }
    }
}
