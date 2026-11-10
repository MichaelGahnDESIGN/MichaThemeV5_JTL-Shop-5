<?php

declare(strict_types=1);

/**
 * Smoke-Test Updates-Bereich, läuft ohne JTL: php tests/update-smoke.php
 * Prüft den Admin-Handler des Update Clients mit Fake-Callbacks und Fake-HTTP. Kein echter Netzabruf, keine echten Schlüssel.
 */

use Plugin\MichaThemeV5\Update\{ClientConfig, Factory, UpdateClient};
use Plugin\MichaThemeV5\Update\Admin\UpdatesPage;
use Plugin\MichaThemeV5\Update\Crypto\KeyRing;
use Plugin\MichaThemeV5\Update\Http\{HttpClient, HttpResponse};
use Plugin\MichaThemeV5\Update\License\NullLicenseProvider;
use Plugin\MichaThemeV5\Update\Store\StateStore;

$tmp = sys_get_temp_dir() . '/mgd-update-smoke-' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);
define('PFAD_ROOT', $tmp . '/');
define('PFAD_COMPILEDIR', 'templates_c/');

// Autoload wie im Plugin (Plugin\MichaThemeV5\Foo > src/Foo.php)
spl_autoload_register(static function (string $c): void {
    $p = 'Plugin\\MichaThemeV5\\';
    if (str_starts_with($c, $p)) {
        $f = __DIR__ . '/../plugins/MichaThemeV5/src/' . str_replace('\\', '/', substr($c, strlen($p))) . '.php';
        if (is_file($f)) {
            require_once $f;
        }
    }
});

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

// Fake HTTP, zählt Aufrufe
final class FakeHttp implements HttpClient
{
    public int $calls = 0;

    public function request(string $method, string $url, array $headers = [], ?string $body = null, int $maxBytes = 1048576): HttpResponse
    {
        $this->calls++;

        return new HttpResponse(503);
    }

    public function download(string $url, array $headers, string $targetFile, int $maxBytes): HttpResponse
    {
        $this->calls++;

        return new HttpResponse(503);
    }
}

// Zustand im Speicher
final class MemState implements StateStore
{
    public int $saves = 0;

    public function __construct(public array $data = [])
    {
    }

    public function load(): array
    {
        return $this->data;
    }

    public function save(array $state): void
    {
        $this->saves++;
        $this->data = $state;
    }
}

// Testschlüssel (nur öffentlich, wird nie zum Signieren benutzt)
$ring = (new KeyRing())->add('test-root-1', 'root', str_repeat("\x01", SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES));

function build(bool $right, bool $csrf, array $state, bool $keys, FakeHttp $http, ?MemState &$st = null, string $name = 'MichaThemeV5'): UpdatesPage
{
    global $ring;
    $st = new MemState($state);
    $cfg = new ClientConfig('michatheme-v5', 'free', '0.5.1', 'shop.example.test', $keys ? $ring : new KeyRing(), sys_get_temp_dir() . '/mgd-x', '5.8.2');
    $client = new UpdateClient($cfg, $http, new NullLicenseProvider(), $st, static fn (): int => 1_800_000_000);

    return new UpdatesPage($client, $st, static fn (): bool => $csrf, static fn (): bool => $right, '<input type="hidden" name="jtl_token" value="T">', $name, '0.5.1', static fn (): int => 1_800_000_000);
}

// 1. ohne Recht: kein Zugriff, kein Netz, nichts gespeichert (GET und POST)
$h = new FakeHttp();
$r = build(false, true, ['enabled' => true], true, $h, $st)->handle('POST', ['mgd_update_action' => 'check_now']);
ok('ohne Recht: Meldung Berechtigung', str_contains($r['html'], 'Berechtigung'));
ok('ohne Recht: kein Netzabruf', $h->calls === 0);
ok('ohne Recht: nichts gespeichert', $st->saves === 0);
ok('ohne Recht: keine Formulare', !str_contains($r['html'], '<form'));
ok('ohne Recht: nichts zum Ausliefern', $r['deliver'] === null);
$r = build(false, true, [], true, $h, $st)->handle('GET', []);
ok('ohne Recht (GET): keine Formulare', !str_contains($r['html'], '<form') && $h->calls === 0);

// 2. ohne CSRF: keine Aktion
foreach (['check_now', 'save_settings', 'download', 'deliver'] as $act) {
    $h = new FakeHttp();
    $r = build(true, false, [], true, $h, $st)->handle('POST', ['mgd_update_action' => $act, 'confirm' => '1', 'checks_enabled' => '1']);
    ok("ohne CSRF ($act): Token-Meldung", str_contains($r['html'], 'Sicherheits-Token ungültig'));
    ok("ohne CSRF ($act): kein Netz, nichts gespeichert, kein Ausliefern", $h->calls === 0 && $st->saves === 0 && $r['deliver'] === null);
}

// 3. GET ohne Einschalten: kein Netzabruf
$h = new FakeHttp();
$r = build(true, true, [], true, $h, $st)->handle('GET', []);
ok('GET ohne Einschalten: kein Netzabruf', $h->calls === 0);
ok('GET ohne Einschalten: Formular zeigt Opt-in', str_contains($r['html'], 'Update-Prüfung einschalten') && !str_contains($r['html'], 'checked'));
$r = build(true, true, ['enabled' => false, 'last_check' => 0], true, $h, $st)->handle('GET', []);
ok('GET mit enabled=false: kein Netzabruf', $h->calls === 0);

// 3b. Gegenprobe: eingeschaltet + alt = ein Abruf (Zähler funktioniert)
$h = new FakeHttp();
build(true, true, ['enabled' => true, 'last_check' => 0], true, $h, $st)->handle('GET', []);
ok('Gegenprobe: eingeschaltet und alt = Abruf', $h->calls > 0);

// 3c. Blocker B1: Schlüssel leer, auch eingeschaltet kein Netz
$h = new FakeHttp();
$r = build(true, true, ['enabled' => true, 'last_check' => 0], false, $h, $st)->handle('POST', ['mgd_update_action' => 'check_now']);
ok('B1 (keine Schlüssel): kein Netzabruf', $h->calls === 0);
ok('B1: Hinweis Update-Kanal nicht eingerichtet', str_contains($r['html'], 'noch nicht eingerichtet'));

// 4. Download ohne Bestätigung: kein Netz
$h = new FakeHttp();
$r = build(true, true, [], true, $h, $st)->handle('POST', ['mgd_update_action' => 'download']);
ok('Download ohne Bestätigung: Hinweis, kein Netz', $h->calls === 0 && str_contains($r['html'], 'Bestätigung'));

// 5. Ausgabe escaped
$evil = '<script>alert(1)</script>';
$h = new FakeHttp();
$state = ['last_result' => ['status' => 'update_available', 'message' => $evil, 'version' => '"><img src=x onerror=1>', 'changelog' => $evil, 'security' => true]];
$r = build(true, true, $state, true, $h, $st, '<b>Name</b>')->handle('GET', []);
ok('Anzeige escaped: kein rohes <script>', !str_contains($r['html'], '<script') && !str_contains($r['html'], '<img'));
ok('Anzeige escaped: &lt;script vorhanden', str_contains($r['html'], '&lt;script&gt;'));
ok('Anzeige escaped: Plugin-Name', !str_contains($r['html'], '<b>Name</b>') && str_contains($r['html'], '&lt;b&gt;Name'));
ok('Formulare tragen CSRF-Feld', substr_count($r['html'], 'name="jtl_token"') >= 3);

// 6. Verdrahtung über die echte Factory::page (Signatur wie in UpdatesPage-Tab)
$names = array_map(static fn (ReflectionParameter $p): string => $p->getName(), (new ReflectionMethod(Factory::class, 'page'))->getParameters());
ok('Factory::page Signatur', array_slice($names, 0, 11) === ['rootKeysFile', 'dataDir', 'product', 'edition', 'pluginName', 'installedVersion', 'domain', 'shopVersion', 'csrfValid', 'canManage', 'csrfField']);
$rk = __DIR__ . '/../plugins/MichaThemeV5/update/root-keys.json';
$page = Factory::page($rk, $tmp . '/data', 'michatheme-v5', 'free', 'MichaThemeV5', '0.5.1', 'shop.example.test', '5.8.2', static fn (): bool => false, static fn (): bool => false, '<input>');
$r = $page->handle('GET', []);
ok('Factory: ohne Recht kein Zugriff', str_contains($r['html'], 'Berechtigung'));
$rootKeys = json_decode((string) file_get_contents($rk), true);
ok('root-keys.json ausgeliefert, ohne Schlüssel (Blocker B1)', is_array($rootKeys) && ($rootKeys['keys'] ?? null) === []);
ok('root-keys.json ohne private Teile', !preg_match('/secret|private|seed/i', (string) file_get_contents($rk)));

// 7. Datenordner mit Sperre
$dir = Plugin\MichaThemeV5\Support\UpdateData::dir();
ok('Datenordner liegt in templates_c', str_contains($dir, '/templates_c/mgd_update_michathemev5'));
ok('Datenordner anlegbar', mkdir(dirname($dir), 0700, true) && Plugin\MichaThemeV5\Support\UpdateData::ensure($dir));
ok('.htaccess Deny vorhanden', str_contains((string) file_get_contents($dir . '/.htaccess'), 'Require all denied') && str_contains((string) file_get_contents($dir . '/.htaccess'), 'Deny from all'));
ok('Datenordner nur Besitzer (0700)', (fileperms($dir) & 0777) === 0700);

// 8. kein Token / Secret im neuen Code
$bad = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../plugins/MichaThemeV5/src/Update', FilesystemIterator::SKIP_DOTS)) as $f) {
    if (preg_match('/BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY|sk_live|ghp_[A-Za-z0-9]{20}/', (string) file_get_contents($f->getPathname()))) {
        $bad++;
    }
}
ok('kein privater Schlüssel im Client-Code', $bad === 0);

// aufräumen (nur eigener Temp-Ordner)
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($it as $f) {
    $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
}
@rmdir($tmp);

echo "\n$count Prüfungen, $fails Fehler\n";
exit($fails === 0 ? 0 : 1);
