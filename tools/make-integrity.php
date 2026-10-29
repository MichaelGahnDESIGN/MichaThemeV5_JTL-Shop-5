<?php
// Schreibt build-integrity.json für das mitgelieferte Template (Version + SHA-256 jeder Datei).
// Aufruf: php tools/make-integrity.php <Template-Ordner> <Version>
if ($argc !== 3 || !is_dir($argv[1]) || !preg_match('/^\d+\.\d+\.\d+$/', $argv[2])) {
    fwrite(STDERR, "Aufruf: php tools/make-integrity.php <Template-Ordner> <x.y.z>\n");
    exit(1);
}
$base = realpath($argv[1]);
$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if ($f->isLink()) {
        fwrite(STDERR, "Symlink im Template: " . $f->getPathname() . "\n");
        exit(1);
    }
    if ($f->isFile()) {
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($base) + 1));
        if ($rel !== 'build-integrity.json') {
            $files[$rel] = hash_file('sha256', $f->getPathname());
        }
    }
}
ksort($files);
file_put_contents($base . '/build-integrity.json', json_encode(['version' => $argv[2], 'files' => $files], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo count($files) . " Dateien eingetragen\n";
