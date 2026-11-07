<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Update/ReleaseChecker.php';

use Plugin\MGDSucheJTLShop\Update\ReleaseChecker;

$valid = [
    'tag_name' => 'mgd-suche-v0.2.0',
    'draft' => false,
    'prerelease' => false,
    'assets' => [[
        'name' => 'MGD-Suche-JTL-Shop-Plugin-0.2.0.zip',
        'browser_download_url' => 'https://github.com/MichaelGahnDESIGN/MichaThemeV5_JTL-Shop-5/releases/download/mgd-suche-v0.2.0/MGD-Suche-JTL-Shop-Plugin-0.2.0.zip',
    ]],
];

if (ReleaseChecker::parseRelease($valid) === null) {
    fwrite(STDERR, "Gültiges Release wurde abgewiesen.\n");
    exit(1);
}

$invalid = $valid;
$invalid['assets'][0]['browser_download_url'] = 'https://example.invalid/unsafe.zip';
if (ReleaseChecker::parseRelease($invalid) !== null) {
    fwrite(STDERR, "Unsicheres Release wurde akzeptiert.\n");
    exit(1);
}

$otherPlugin = $valid;
$otherPlugin['tag_name'] = 'v0.6.0';
$otherPlugin['assets'] = [];
$newest = ReleaseChecker::newestMatchingRelease([$otherPlugin, $valid]);
if ($newest === null || $newest['tag'] !== 'mgd-suche-v0.2.0') {
    fwrite(STDERR, "Fremdes Repository-Release wurde nicht sauber getrennt.\n");
    exit(1);
}

echo "ReleaseChecker: OK\n";
