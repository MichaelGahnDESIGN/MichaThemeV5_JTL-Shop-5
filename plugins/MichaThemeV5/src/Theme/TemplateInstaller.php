<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Theme;

defined('PFAD_ROOT') || exit;

/**
 * Template bereitstellen. Nie still und nie automatisch:
 *  1. Nur per Knopf auf der Info-Seite, mit Rückfrage und Häkchen "Ich habe ein Backup" (macht die Seite, nicht diese Klasse).
 *  2. Quelle ist nur das MITGELIEFERTE Paket im Plugin (template-package/, kein Download), Prüfsumme pro Datei gegen build-integrity.json.
 *  3. Ein vorhandenes Template wird zuerst als ZIP gesichert (Plugin-Ordner backup/), dann erst ausgetauscht, bei Fehler zurückgespielt.
 *  4. Aktiviert wird NIE hier. Das bleibt ein Klick des Händlers (Darstellung > Template).
 * Alle Pfade kommen aus dem Konstruktor, nie aus Eingaben.
 */
final class TemplateInstaller
{
    public const NAME = 'MichaThemeV5';
    public const KEEP_BACKUPS = 5;

    private readonly string $root;
    private readonly string $bundle;
    private readonly string $backupDir;
    private readonly string $stageDir;

    public function __construct(string $shopRoot, string $pluginDir, ?string $stageDir = null)
    {
        $this->root = rtrim($shopRoot, '/\\');
        $this->bundle = rtrim($pluginDir, '/\\') . '/template-package/' . self::NAME;
        $this->backupDir = rtrim($pluginDir, '/\\') . '/backup';
        $this->stageDir = rtrim($stageDir ?? ($this->root . '/templates_c'), '/\\');
    }

    public function targetDir(): string
    {
        return $this->root . '/templates/' . self::NAME;
    }

    /** @return array{version:string,files:array<string,string>}|null */
    public function manifest(): ?array
    {
        $f = $this->bundle . '/build-integrity.json';
        if (!is_file($f)) {
            return null;
        }
        $d = json_decode((string) file_get_contents($f), true);

        return is_array($d) && is_string($d['version'] ?? null) && is_array($d['files'] ?? null) ? $d : null;
    }

    public function bundledVersion(): ?string
    {
        return $this->manifest()['version'] ?? null;
    }

    public static function versionOf(string $dir): ?string
    {
        $xml = @file_get_contents($dir . '/template.xml', false, null, 0, 65536);

        return is_string($xml) && preg_match('~<Version>\s*([0-9]+\.[0-9]+\.[0-9]+)\s*</Version>~', $xml, $m) === 1 ? $m[1] : null;
    }

    public function installedVersion(): ?string
    {
        return is_dir($this->targetDir()) ? self::versionOf($this->targetDir()) : null;
    }

    public function novaPresent(): bool
    {
        return is_file($this->root . '/templates/NOVA/template.xml');
    }

    public function templatesWritable(): bool
    {
        return is_dir($this->root . '/templates') && is_writable($this->root . '/templates');
    }

    /** @return list<string> Backup-ZIPs, neueste zuerst */
    public function backups(): array
    {
        $list = glob($this->backupDir . '/template-' . self::NAME . '-*.zip') ?: [];
        rsort($list);

        return $list;
    }

    /** Alles prüfen, bevor etwas angefasst wird. null = in Ordnung, sonst eine verständliche Meldung. */
    public function precheck(): ?string
    {
        if ($this->manifest() === null) {
            return 'Das Plugin enthält kein Template-Paket (build-integrity.json fehlt). Bitte das komplette Plugin-ZIP von der Download-Seite verwenden.';
        }
        if (!$this->novaPresent()) {
            return 'Das Basis-Template NOVA fehlt im Ordner templates/. MichaThemeV5 baut darauf auf.';
        }
        if (!$this->templatesWritable()) {
            return 'Der Ordner templates/ ist nicht beschreibbar. Bitte den Ordner von Hand kopieren (Anleitung in der INSTALL.md) oder die Rechte beim Hoster anpassen.';
        }
        if (!is_dir($this->stageDir) || !is_writable($this->stageDir)) {
            return 'Der Arbeitsordner templates_c/ ist nicht beschreibbar.';
        }
        if (is_dir($this->targetDir()) && !class_exists(\ZipArchive::class)) {
            return 'Für die Sicherung des bisherigen Templates fehlt die PHP-Erweiterung zip. Bitte den Ordner von Hand sichern und kopieren.';
        }

        return $this->verifyBundle();
    }

    public function verifyBundle(): ?string
    {
        $m = $this->manifest();
        if ($m === null) {
            return 'Das Plugin enthält kein Template-Paket (build-integrity.json fehlt).';
        }

        return self::verifyDir($this->bundle, $m['files']);
    }

    /** @param array<string,string> $files relativer Pfad => sha256. null = alles stimmt. */
    public static function verifyDir(string $dir, array $files): ?string
    {
        $base = realpath($dir);
        if ($base === false || !is_dir($base)) {
            return 'Ordner nicht gefunden.';
        }
        $seen = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isLink()) {
                return 'Unerwarteter Verweis (Symlink) im Template-Paket.';
            }
            if (!$f->isFile()) {
                continue;
            }
            $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($base) + 1));
            if ($rel === 'build-integrity.json') {
                continue;
            }
            if (!isset($files[$rel])) {
                return 'Unerwartete Datei im Template-Paket: ' . $rel;
            }
            if (!hash_equals((string) $files[$rel], (string) hash_file('sha256', $f->getPathname()))) {
                return 'Prüfsumme stimmt nicht: ' . $rel;
            }
            $seen[$rel] = true;
        }
        foreach ($files as $rel => $_) {
            if (!isset($seen[$rel])) {
                return 'Datei fehlt im Template-Paket: ' . $rel;
            }
        }

        return null;
    }

    /**
     * Austausch. Aufrufer hat Rechte, Token, Häkchen und Rückfrage schon geprüft.
     *
     * @return array{ok:bool,message:string,backup:?string}
     */
    public function install(): array
    {
        if (($err = $this->precheck()) !== null) {
            return ['ok' => false, 'message' => $err, 'backup' => null];
        }
        $manifest = $this->manifest();
        $files = $manifest['files'] ?? [];
        $target = $this->targetDir();
        $backup = null;

        // 1) Sicherung des bisherigen Templates
        if (is_dir($target)) {
            $backup = $this->backupTarget();
            if ($backup === null) {
                return ['ok' => false, 'message' => 'Die Sicherung des bisherigen Templates ist fehlgeschlagen. Es wurde nichts verändert.', 'backup' => null];
            }
        }

        // 2) Neues Template in den Arbeitsordner kopieren und prüfen
        $token = bin2hex(random_bytes(6));
        $new = $this->stageDir . '/mt_new_' . $token;
        $old = $this->stageDir . '/mt_old_' . $token;
        if (!$this->copyTree($this->bundle, $new) || self::verifyDir($new, $files) !== null) {
            $this->removeTree($new);

            return ['ok' => false, 'message' => 'Das Kopieren in den Arbeitsordner ist fehlgeschlagen. Es wurde nichts verändert.', 'backup' => $backup];
        }
        @unlink($new . '/build-integrity.json');

        // 3) Austausch: altes zur Seite, neues an die Stelle, bei Fehler zurück
        $moved = false;
        if (is_dir($target)) {
            if (!@rename($target, $old)) {
                $this->removeTree($new);

                return ['ok' => false, 'message' => 'Das bisherige Template konnte nicht ersetzt werden. Es wurde nichts verändert.', 'backup' => $backup];
            }
            $moved = true;
        }
        if (!@rename($new, $target) || self::verifyDir($target, $this->withoutIntegrity($files)) !== null) {
            $this->removeTree($target);
            if ($moved) {
                @rename($old, $target);
            }
            $this->removeTree($new);

            return ['ok' => false, 'message' => 'Der Austausch ist fehlgeschlagen. Das bisherige Template wurde ' . ($moved ? 'zurückgespielt.' : 'nicht verändert.'), 'backup' => $backup];
        }
        if ($moved) {
            $this->removeTree($old);
        }

        return ['ok' => true, 'message' => 'Template kopiert.', 'backup' => $backup];
    }

    /** @param array<string,string> $files */
    private function withoutIntegrity(array $files): array
    {
        unset($files['build-integrity.json']);

        return $files;
    }

    /** ZIP-Sicherung des bisherigen Template-Ordners. Gibt den Dateinamen zurück oder null. */
    private function backupTarget(): ?string
    {
        if (!is_dir($this->backupDir) && !@mkdir($this->backupDir, 0750, true)) {
            return null;
        }
        // Ordner nicht von außen lesbar, Liste leer
        @file_put_contents($this->backupDir . '/.htaccess', "Require all denied\nDeny from all\n");
        @file_put_contents($this->backupDir . '/index.html', '');
        $ver = $this->installedVersion() ?? 'alt';
        $zipPath = $this->backupDir . '/template-' . self::NAME . '-' . preg_replace('/[^0-9A-Za-z.]/', '', $ver) . '-' . gmdate('Ymd-His') . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::EXCL) !== true) {
            return null;
        }
        $base = realpath($this->targetDir());
        if ($base === false) {
            return null;
        }
        $n = 0;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isLink() || !$f->isFile()) {
                continue;
            }
            $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($base) + 1));
            if (!$zip->addFile($f->getPathname(), self::NAME . '/' . $rel)) {
                $zip->close();
                @unlink($zipPath);

                return null;
            }
            $n++;
        }
        if (!$zip->close() || !is_file($zipPath)) {
            @unlink($zipPath);

            return null;
        }
        // Gegenprobe: ZIP lässt sich öffnen und hat alle Dateien
        $check = new \ZipArchive();
        if ($check->open($zipPath) !== true || $check->numFiles !== $n) {
            @unlink($zipPath);

            return null;
        }
        $check->close();
        @chmod($zipPath, 0640);
        $this->pruneBackups();

        return basename($zipPath);
    }

    private function pruneBackups(): void
    {
        foreach (array_slice($this->backups(), self::KEEP_BACKUPS) as $old) {
            if (preg_match('~/template-' . self::NAME . '-[0-9A-Za-z.]+-\d{8}-\d{6}\.zip$~', $old) === 1) {
                @unlink($old);
            }
        }
    }

    private function copyTree(string $from, string $to): bool
    {
        $base = realpath($from);
        if ($base === false || !@mkdir($to, 0755, true)) {
            return false;
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($it as $f) {
            if ($f->isLink()) {
                return false;
            }
            $rel = substr($f->getPathname(), strlen($base) + 1);
            $dest = $to . '/' . $rel;
            if ($f->isDir()) {
                if (!is_dir($dest) && !@mkdir($dest, 0755, true)) {
                    return false;
                }
            } elseif (!@copy($f->getPathname(), $dest)) {
                return false;
            } else {
                @chmod($dest, 0644);
            }
        }

        return true;
    }

    /** Löscht nur einen Ordner direkt im Arbeitsordner (mt_new_*, mt_old_*) oder das Ziel selbst. */
    private function removeTree(string $dir): void
    {
        $real = realpath($dir);
        if ($real === false || !is_dir($real)) {
            return;
        }
        $okStage = str_starts_with($real, (string) realpath($this->stageDir) . '/mt_');
        $okTarget = $real === realpath($this->targetDir());
        if (!$okStage && !$okTarget) {
            return;
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($real, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) {
            if ($f->isDir() && !$f->isLink()) {
                @rmdir($f->getPathname());
            } else {
                @unlink($f->getPathname());
            }
        }
        @rmdir($real);
    }
}
