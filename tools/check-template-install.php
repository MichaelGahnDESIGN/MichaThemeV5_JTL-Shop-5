<?php

declare(strict_types=1);

// Prüft den gebauten Installer in einem leeren Testordner, ohne JTL-Datenbank oder echten Shop.
if ($argc !== 3 || !is_file($argv[1]) || !preg_match('/^\d+\.\d+\.\d+$/D', $argv[2])) {
    fwrite(STDERR, "Aufruf: php tools/check-template-install.php Plugin-ZIP Version\n");
    exit(1);
}
$root = sys_get_temp_dir() . '/mt-package-test-' . bin2hex(random_bytes(8));
mkdir($root, 0700);
$check = static function (bool $ok, string $message): void {
    if (!$ok) {
        throw new RuntimeException($message);
    }
};
try {
    $zip = new ZipArchive();
    $check($zip->open($argv[1]) === true, 'ZIP nicht lesbar');
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        $check(is_string($name) && !str_starts_with($name, '/') && !str_contains($name, '\\') && !in_array('..', explode('/', $name), true), 'Unsicherer Paketpfad');
    }
    $check($zip->extractTo($root . '/plugins'), 'ZIP nicht entpackbar');
    $zip->close();
    mkdir($root . '/templates/NOVA', 0700, true);
    mkdir($root . '/templates_c', 0700);
    file_put_contents($root . '/templates/NOVA/template.xml', '<Template><Version>1.0.0</Version></Template>');
    define('PFAD_ROOT', $root . '/');
    $plugin = $root . '/plugins/MichaThemeV5';
    require $plugin . '/src/Theme/TemplateInstaller.php';
    $installer = new Plugin\MichaThemeV5\Theme\TemplateInstaller($root, $plugin);
    $check($installer->precheck() === null, 'Paket-Vorprüfung fehlgeschlagen');
    $first = $installer->install();
    $check($first['ok'] && $first['backup'] === null, 'Erstinstallation fehlgeschlagen');
    $check($installer->installedVersion() === $argv[2], 'Installierte Version falsch');
    $check($installer::verifyDir($installer->targetDir(), $installer->manifest()['files']) === null, 'Installierte Dateien weichen ab');
    file_put_contents($installer->targetDir() . '/test-bestand.txt', 'Bisheriger Stand');
    $second = $installer->install();
    $check($second['ok'] && is_string($second['backup']), 'Update ohne Sicherung');
    $backup = new ZipArchive();
    $check($backup->open($plugin . '/backup/' . $second['backup']) === true, 'Sicherung nicht lesbar');
    $check($backup->getFromName('MichaThemeV5/test-bestand.txt') === 'Bisheriger Stand', 'Bisheriger Stand nicht gesichert');
    $backup->close();
    $target = $installer->targetDir() . '/template.xml';
    $before = hash_file('sha256', $target);
    file_put_contents($plugin . '/template-package/MichaThemeV5/template.xml', 'Verändertes Paket');
    $check($installer->verifyBundle() !== null, 'Manipulation nicht erkannt');
    $check(!$installer->install()['ok'] && hash_file('sha256', $target) === $before, 'Ungültiges Paket hat den Bestand verändert');
    echo json_encode(['version' => $argv[2], 'first_install' => true, 'update_backup' => true, 'tamper_rejected' => true, 'environment' => 'isolierter Dateisystemtest, kein nativer JTL-Shop'], JSON_UNESCAPED_UNICODE) . "\n";
} finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($root);
}
