<?php

declare(strict_types=1);

/**
 * Smoke-Test Free-Lizenz Anmeldung, läuft ohne JTL: php plugins/MichaThemeV5NotificationCenter/tests/InstallSmokeTest.php
 * Fake-HTTP, kein echter Netzabruf.
 */

use Plugin\MichaThemeV5NotificationCenter\Install\{InstallClient, JsonFileStore, Response, Settings, Transport};
use Plugin\MichaThemeV5NotificationCenter\Install\Platform\Jtl;
use Plugin\MichaThemeV5NotificationCenter\Update\UpdateBridge;
use Plugin\MichaThemeV5NotificationCenter\UpdateClient\Admin\InstallReporting;

$tmp = sys_get_temp_dir() . '/mgd-install-smoke-' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);
// UpdateBridge bricht ohne PFAD_ROOT still ab
define('PFAD_ROOT', $tmp . '/');
define('PFAD_COMPILEDIR', 'templates_c/');

require_once __DIR__ . '/../src/Update/UpdateBridge.php';
UpdateBridge::registerAutoload(__DIR__ . '/..');
// zweimal registrieren darf nix doppelt machen
UpdateBridge::registerAutoload(__DIR__ . '/..');

$fails = 0;
$count = 0;
function ok(string $name, bool $cond): void
{
    global $fails, $count;
    $count++;
    echo ($cond ? 'OK   ' : 'FAIL ') . $name . "\n";
    if (!$cond) {
        $fails++;
    }
}

// Fake HTTP, merkt sich die Aufrufe
final class FakeTransport implements Transport
{
    /** @var list<array{string,string,array<string,string>}> */
    public array $calls = [];

    public function __construct(private Response $answer)
    {
    }

    public function send(string $method, string $url, array $headers): Response
    {
        $this->calls[] = [$method, $url, $headers];

        return $this->answer;
    }
}

// 1. Plattform-Anbindung
$c = Jtl::client($tmp, UpdateBridge::PRODUCT, '1.5.0', 'shop.example.test');
ok('Jtl::client liefert Client', $c instanceof InstallClient);
ok('Produktname wie Update-JSON', $c?->product() === 'mgd-notification-center');
ok('Schalter ab Werk an', $c?->isEnabled() === true);
ok('falscher Produktname > null statt Exception', Jtl::client($tmp, 'Falsch Name', '1.5.0', 'x.test') === null);

// 2. Meldung mit Fake-HTTP
$fake = new FakeTransport(new Response(200, ['x-mgd-install-key' => str_repeat('k', 32)], '{}'));
$now = 1_800_000_000;
$client = new InstallClient($fake, new JsonFileStore($tmp . '/fake.json'), 'mgd-notification-center', 'jtl5', '1.5.0', 'shop.example.test', static function () use (&$now): int {
    return $now;
});
ok('erste Meldung klappt', $client->ping(true) === true);
$h = $fake->calls[0][2] ?? [];
ok('nur erlaubte Angaben gesendet', array_keys($h) === ['X-MGD-Install-Id', 'X-MGD-Platform', 'X-MGD-Domain', 'X-MGD-Version']);
ok('Shopsystem jtl5', ($h['X-MGD-Platform'] ?? '') === 'jtl5');
ok('Adresse auf theme.michael-gahn.de', str_starts_with($fake->calls[0][1] ?? '', 'https://theme.michael-gahn.de/api/v1/updates/mgd-notification-center.json'));
ok('zweite Meldung am selben Tag bleibt aus', $client->ping() === false && count($fake->calls) === 1);
$now += 86_401;
ok('nach einem Tag wieder melden, mit Schlüssel', $client->ping() === true && str_starts_with($fake->calls[1][2]['Authorization'] ?? '', 'Bearer '));
$client->setEnabled(false);
ok('abschalten löscht Eintrag auf der Plattform', ($fake->calls[2][0] ?? '') === 'DELETE');
ok('abgeschaltet meldet nix mehr', $client->ping(true) === false && count($fake->calls) === 3);
$client->setEnabled(true);
ok('wieder an', $client->isEnabled() === true);

// 3. Plattform weg > kein Fehler nach außen
$down = new InstallClient(new FakeTransport(new Response(0)), new JsonFileStore($tmp . '/down.json'), 'mgd-notification-center', 'jtl5', '1.5.0', 'shop.example.test');
ok('Plattform weg > false, keine Exception', $down->ping(true) === false);
ok('Status sagt später neu versuchen', str_contains($down->status(), 'nicht erreichbar'));

// 4. Hinweistext freigegeben, Schalter auf der Update-Seite
ok('Hinweistext ohne Entwurf', str_contains(Settings::HINT, 'höchstens einmal am Tag') && stripos(Settings::HINT, 'entwurf') === false);
$r = new InstallReporting(static fn () => null, static fn (): bool => true, static fn (bool $a) => null, static fn (): string => 'Noch nicht gemeldet.', Settings::HINT);
ok('InstallReporting baut', $r->hint === Settings::HINT);

// 4b. Update-Seite zeigt Schalter mit Hinweis (Admin mit Recht)
$on = true;
$rep = new InstallReporting(static fn () => null, static function () use (&$on): bool {
    return $on;
}, static function (bool $a) use (&$on): void {
    $on = $a;
}, static fn (): string => 'Noch nicht gemeldet.', Settings::HINT);
$page = \Plugin\MichaThemeV5NotificationCenter\UpdateClient\Factory::page(__DIR__ . '/../update/root-keys.json', $tmp . '/data', 'mgd-notification-center', 'free', 'MGD Notification Center', '1.5.0', 'shop.example.test', '5.8.2', static fn (): bool => true, static fn (): bool => true, '<input type="hidden" name="jtl_token" value="T">', null, ['info.xml'], null, $rep);
$html = $page->handle('GET', [])['html'];
ok('Update-Seite: Schalter angehakt', str_contains($html, 'name="install_report" value="1" checked'));
ok('Update-Seite: Hinweistext', str_contains($html, 'höchstens einmal am Tag'));
$page->handle('POST', ['mgd_update_action' => 'save_install']);
ok('Update-Seite: abhaken schaltet aus', $on === false);
$page->handle('POST', ['mgd_update_action' => 'save_install', 'install_report' => '1']);
ok('Update-Seite: anhaken schaltet an', $on === true);
$noRight = \Plugin\MichaThemeV5NotificationCenter\UpdateClient\Factory::page(__DIR__ . '/../update/root-keys.json', $tmp . '/data', 'mgd-notification-center', 'free', 'MGD Notification Center', '1.5.0', 'shop.example.test', '5.8.2', static fn (): bool => true, static fn (): bool => false, '<input>', null, ['info.xml'], null, $rep);
ok('ohne Recht kein Schalter', !str_contains($noRight->handle('GET', [])['html'], 'install_report'));

// 5. Kurztext in info.xml
$xml = (string) file_get_contents(__DIR__ . '/../info.xml');
ok('Kurztext in info.xml', str_contains($xml, 'Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar in den Einstellungen.'));

exec('rm -rf ' . escapeshellarg($tmp));
@rmdir($tmp);

echo "\n$count Prüfungen, $fails Fehler\n";
exit($fails === 0 ? 0 : 1);
