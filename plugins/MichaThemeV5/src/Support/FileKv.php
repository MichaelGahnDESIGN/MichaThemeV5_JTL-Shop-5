<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Support;

defined('PFAD_ROOT') || exit;

/**
 * Kleiner Datei-Zwischenspeicher in templates_c (Katalog-Liste, Pausen). Kaputt oder nicht schreibbar = einfach kein Cache.
 * Nur JSON, kein PHP. Dateiname aus Hash, nie aus Eingaben.
 */
final class FileKv
{
    private string $dir;

    public function __construct(?string $dir = null)
    {
        $dir ??= defined('PFAD_COMPILEDIR') ? PFAD_ROOT . PFAD_COMPILEDIR : sys_get_temp_dir();
        $this->dir = rtrim($dir, '/\\');
    }

    private function file(string $key): string
    {
        return $this->dir . '/mt_v5_kv_' . substr(hash('sha256', PFAD_ROOT . '|' . $key), 0, 24) . '.json';
    }

    public function get(string $key): mixed
    {
        $f = $this->file($key);
        $raw = is_file($f) ? @file_get_contents($f, false, null, 0, 2_000_000) : false;
        if (!is_string($raw)) {
            return null;
        }
        $d = json_decode($raw, true, 32);
        if (!is_array($d) || !is_int($d['e'] ?? null) || $d['e'] < time() || !array_key_exists('v', $d)) {
            return null;
        }

        return $d['v'];
    }

    public function set(string $key, mixed $value, int $ttl): void
    {
        $json = json_encode(['e' => time() + max(1, $ttl), 'v' => $value]);
        if ($json === false || !is_dir($this->dir) || !is_writable($this->dir)) {
            return;
        }
        // erst Temp-Datei, dann umbenennen
        $tmp = @tempnam($this->dir, 'mtkv');
        if ($tmp === false) {
            return;
        }
        if (@file_put_contents($tmp, $json) === false || !@rename($tmp, $this->file($key))) {
            @unlink($tmp);
            return;
        }
        @chmod($this->file($key), 0640);
    }
}
