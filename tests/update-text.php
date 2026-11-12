<?php

declare(strict_types=1);

// Erfolgsmeldung nach dem Download nennt nur, was wirklich geprüft wurde
define('PFAD_ROOT', __DIR__ . '/');
require dirname(__DIR__) . '/plugins/MichaThemeV5/src/autoload.php';
use Plugin\MichaThemeV5\Update\{UpdateResult, Admin\UpdatesPage};
$simple = UpdatesPage::verifiedText(new UpdateResult('update_available', 'x', null, null, ['mode' => 'simple']));
$signed = UpdatesPage::verifiedText(new UpdateResult('update_available', 'x', null, null, []));
if (str_contains($simple, 'Signatur') || !str_contains($simple, 'SHA-256') || !str_contains($signed, 'Signatur')) {
    throw new RuntimeException('Erfolgsmeldung behauptet eine Prüfung, die nicht lief.');
}
echo "Update-Bereich: Erfolgsmeldung passt zum Prüfmodus.\n";
