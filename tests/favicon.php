<?php

declare(strict_types=1);

// Favicon: MichaTheme-Standard im Template, eigenes per Upload, bleibt beim Template-Update erhalten
define('PFAD_ROOT', sys_get_temp_dir() . '/');
$root = dirname(__DIR__);
require $root . '/plugins/MichaThemeV5/src/Theme/TemplateInstaller.php';

use Plugin\MichaThemeV5\Theme\TemplateInstaller;

$fails = 0;
$ok = static function (string $name, bool $cond) use (&$fails): void {
    echo ($cond ? 'OK   ' : 'FEHL ') . $name . "\n";
    $fails += $cond ? 0 : 1;
};

// Standard-Favicon liegt da, wo JTL am Ende nachschaut
$def = $root . '/templates/MichaThemeV5/favicon/favicon-default.png';
$info = is_file($def) ? getimagesize($def) : false;
$ok('favicon-default.png vorhanden', is_file($def));
$ok('favicon-default.png ist PNG, quadratisch, mind. 192 px', $info !== false && $info[2] === IMAGETYPE_PNG && $info[0] === $info[1] && $info[0] >= 192);

// Upload-Feld in den Template-Einstellungen (Ziel favicon/favicon.ico wie bei NOVA)
$xml = simplexml_load_file($root . '/templates/MichaThemeV5/template.xml');
$fav = $xml !== false ? $xml->xpath('//Section[@Key="theme"]/Setting[@Key="favicon"]') : [];
$ok('Einstellung favicon im Abschnitt theme', count($fav) === 1);
$ok('Upload nach favicon/favicon.ico', count($fav) === 1 && (string) $fav[0]['Type'] === 'upload' && (string) $fav[0]['target'] === 'favicon/' && (string) $fav[0]['targetFileName'] === 'favicon.ico');

// eigenes Favicon wird beim Update mitgenommen, ohne eigenes bleibt nur der Standard
$tmp = sys_get_temp_dir() . '/mt-fav-' . bin2hex(random_bytes(4));
mkdir($tmp . '/alt/favicon', 0700, true);
mkdir($tmp . '/neu/favicon', 0700, true);
copy($def, $tmp . '/neu/favicon/favicon-default.png');
file_put_contents($tmp . '/alt/favicon/favicon.ico', 'eigenes-logo');
TemplateInstaller::keepOwnFavicon($tmp . '/alt', $tmp . '/neu');
$ok('eigenes Favicon nach Update noch da', @file_get_contents($tmp . '/neu/favicon/favicon.ico') === 'eigenes-logo');
$ok('Standard-Favicon bleibt daneben', is_file($tmp . '/neu/favicon/favicon-default.png'));

mkdir($tmp . '/alt2', 0700);
mkdir($tmp . '/neu2', 0700);
TemplateInstaller::keepOwnFavicon($tmp . '/alt2', $tmp . '/neu2');
$ok('ohne eigenes Favicon wird nichts angelegt', !is_file($tmp . '/neu2/favicon/favicon.ico'));

exec('rm -rf ' . escapeshellarg($tmp));
exit($fails === 0 ? 0 : 1);
