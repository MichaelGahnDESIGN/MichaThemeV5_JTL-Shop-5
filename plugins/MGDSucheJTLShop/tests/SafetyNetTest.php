<?php

declare(strict_types=1);

// Sicherheitsnetz Test, läuft ohne JTL: php plugins/MGDSucheJTLShop/tests/SafetyNetTest.php

use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use JTL\Shop;
use Plugin\MGDSucheJTLShop\Bootstrap;
use Plugin\MGDSucheJTLShop\Search\CharacteristicSuggestionProvider;
use Plugin\MGDSucheJTLShop\Search\SearchConfig;
use Plugin\MGDSucheJTLShop\Search\SearchPageEnhancer;
use Plugin\MGDSucheJTLShop\Search\SearchTermNormalizer;

$pluginDir = dirname(__DIR__);
require_once __DIR__ . '/support/jtl-stubs.php';
require_once $pluginDir . '/Bootstrap.php';
foreach (['SearchConfig', 'SearchTermNormalizer', 'CharacteristicSuggestionProvider', 'SearchPageEnhancer'] as $class) {
    require_once $pluginDir . '/src/Search/' . $class . '.php';
}

$failed = 0;
$total = 0;
$check = static function (string $name, bool $ok) use (&$failed, &$total): void {
    ++$total;
    $failed += $ok ? 0 : 1;
    echo ($ok ? 'OK   ' : 'FAIL ') . $name . "\n";
};

// läuft durch ohne Throwable?
$survives = static function (callable $run): bool {
    try {
        $run();

        return true;
    } catch (\Throwable) {
        return false;
    }
};

// Plugin Attrappe mit Einstellungen
$makePlugin = static function (bool $pathsThrow = false): object {
    return new class ($pathsThrow) {
        public function __construct(private readonly bool $pathsThrow)
        {
        }

        public function getConfig(): object
        {
            return new class {
                public function getValue(string $key): mixed
                {
                    return null;
                }
            };
        }

        public function getPaths(): object
        {
            if ($this->pathsThrow) {
                throw new \RuntimeException('Pfade kaputt');
            }

            return new class {
                public function getBaseURL(): string
                {
                    return 'https://shop.example.test/plugins/MGDSucheJTLShop/';
                }
            };
        }
    };
};

// Dokument Attrappe, sammelt was eingefügt wird
$makeDocument = static function (bool $findThrows = false): object {
    return new class ($findThrows) {
        public string $html = '';

        public function __construct(private readonly bool $findThrows)
        {
        }

        public function find(string $selector): object
        {
            if ($this->findThrows) {
                throw new \Error('phpQuery kaputt');
            }

            return $this;
        }

        public function first(): object
        {
            return $this;
        }

        public function append(string $html): void
        {
            $this->html .= $html;
        }

        public function prepend(string $html): void
        {
            $this->html .= $html;
        }
    };
};

// Datenbank Attrappe
$makeDatabase = static function (bool $throws): object {
    return new class ($throws) {
        public function __construct(private readonly bool $throws)
        {
        }

        /** @return list<object> */
        public function getObjects(string $sql, array $params): array
        {
            if ($this->throws) {
                throw new \RuntimeException('DB weg');
            }

            return [(object) ['id' => 7, 'characteristicName' => 'Material', 'valueName' => 'Baumwolle']];
        }
    };
};

$errors = [];
$makeEnhancer = static function (object $plugin, object $database) use (&$errors): SearchPageEnhancer {
    $config = new SearchConfig($plugin);
    $provider = new CharacteristicSuggestionProvider($database, new SearchTermNormalizer(), $config, 1);

    return new SearchPageEnhancer($plugin, $provider, $config, static function (\Throwable $e) use (&$errors): void {
        $errors[] = $e->getMessage();
    });
};

// --- Hook im Frontend ---
$_GET['qs'] = 'Baumwolle';

$document = $makeDocument();
$enhancer = $makeEnhancer($makePlugin(), $makeDatabase(false));
$check('Normalfall: kein Fehler', $survives(static fn () => $enhancer->inject(['document' => $document])));
$check('Normalfall: Assets und Hinweis eingefügt', str_contains($document->html, 'mgd-search.css') && str_contains($document->html, '?m=7'));
$check('Normalfall: nichts gemeldet', $errors === []);
// Suchformular gehört dem Theme, da darf nix rein
$check('Normalfall: kein Text am Suchfeld', !str_contains($document->html, 'field-hint'));
$searchJs = (string) file_get_contents($pluginDir . '/frontend/js/mgd-search.js');
$check('JS fasst das Suchfeld nicht an', !str_contains($searchJs, 'insertAdjacent') && !str_contains($searchJs, 'querySelector') && !str_contains($searchJs, 'createElement'));
$check('CSS ohne Suchfeld-Hinweis', !str_contains((string) file_get_contents($pluginDir . '/frontend/css/mgd-search.css'), 'field-hint'));

// Tabs im Backend: Einstellungen, Updates, Plugin-Empfehlungen
$menu = simplexml_load_file($pluginDir . '/info.xml')->Install->Adminmenu;
$tabs = [];
foreach ($menu->children() as $entry) {
    $tabs[(int) $entry['sort']] = (string) $entry->Name;
}
ksort($tabs);
$check('Tab-Reihenfolge stimmt', array_values($tabs) === ['Einstellungen', 'Updates', 'Plugin-Empfehlungen']);
$check('Einstellung für Suchfeld-Hinweis ist weg', !str_contains((string) file_get_contents($pluginDir . '/info.xml'), 'show_field_hint'));

$errors = [];
$enhancer = $makeEnhancer($makePlugin(true), $makeDatabase(false));
$check('Plugin-Pfade werfen: Seite läuft weiter', $survives(static fn () => $enhancer->inject(['document' => $makeDocument()])));
$check('Plugin-Pfade werfen: Fehler gemeldet', $errors === ['Pfade kaputt']);

$errors = [];
$enhancer = $makeEnhancer($makePlugin(), $makeDatabase(false));
$check('Dokument wirft Error: Seite läuft weiter', $survives(static fn () => $enhancer->inject(['document' => $makeDocument(true)])));
$check('Dokument wirft Error: Fehler gemeldet', $errors === ['phpQuery kaputt']);

$errors = [];
$document = $makeDocument();
$enhancer = $makeEnhancer($makePlugin(), $makeDatabase(true));
$check('Datenbank wirft: Seite läuft weiter', $survives(static fn () => $enhancer->inject(['document' => $document])));
$check('Datenbank wirft: kein Hinweisblock', !str_contains($document->html, 'mgd-search-feature-hints'));
$check('Datenbank wirft: Fehler gemeldet', $errors === ['DB weg']);

// Melder selbst kaputt, darf auch nichts kippen
$config = new SearchConfig($makePlugin(true));
$provider = new CharacteristicSuggestionProvider($makeDatabase(false), new SearchTermNormalizer(), $config, 1);
$enhancer = new SearchPageEnhancer($makePlugin(true), $provider, $config, static function (\Throwable $e): void {
    throw new \RuntimeException('Melder kaputt');
});
$check('Melder wirft: Seite läuft weiter', $survives(static fn () => $enhancer->inject(['document' => $makeDocument()])));

// --- Start des Plugins ---
Bootstrapper::$plugin = $makePlugin();
$dispatcher = new Dispatcher();
$check('Start normal: kein Fehler', $survives(static fn () => (new Bootstrap())->boot($dispatcher)));
$check('Start normal: Hook angemeldet', $dispatcher->events === ['shop.hook.' . HOOK_SMARTY_OUTPUTFILTER]);
$check('Start normal: kein Logeintrag', Shop::$log === []);

Bootstrapper::$pluginThrows = true;
$dispatcher = new Dispatcher();
$check('Start mit Fehler: Shop läuft weiter', $survives(static fn () => (new Bootstrap())->boot($dispatcher)));
$check('Start mit Fehler: kein Hook angemeldet', $dispatcher->events === []);
$check('Start mit Fehler: Logeintrag mit Pluginname', count(Shop::$log) === 1 && str_starts_with(Shop::$log[0], 'MGDSucheJTLShop: '));

Shop::$logThrows = true;
$check('Start mit Fehler und ohne Log: Shop läuft weiter', $survives(static fn () => (new Bootstrap())->boot(new Dispatcher())));
Shop::$logThrows = false;
Bootstrapper::$pluginThrows = false;

// --- fehlende oder kaputte Plugin-Datei (eigener PHP Prozess, Kopie im Temp-Ordner) ---
$runBroken = static function (callable $breakIt) use ($pluginDir): string {
    $copy = sys_get_temp_dir() . '/mgd-suche-netz-' . bin2hex(random_bytes(4));
    mkdir($copy . '/src/Search', 0700, true);
    mkdir($copy . '/src/Catalog', 0700, true);
    copy($pluginDir . '/Bootstrap.php', $copy . '/Bootstrap.php');
    foreach (glob($pluginDir . '/src/Search/*.php') ?: [] as $file) {
        copy($file, $copy . '/src/Search/' . basename($file));
    }
    copy($pluginDir . '/src/Catalog/PluginCatalogProvider.php', $copy . '/src/Catalog/PluginCatalogProvider.php');
    $breakIt($copy);

    $script = $copy . '/run.php';
    file_put_contents($script, '<?php require ' . var_export(__DIR__ . '/support/jtl-stubs.php', true) . ';'
        . ' require ' . var_export($copy . '/Bootstrap.php', true) . ';'
        . ' \JTL\Plugin\Bootstrapper::$plugin = new \stdClass();'
        . ' $d = new \JTL\Events\Dispatcher();'
        . ' (new \Plugin\MGDSucheJTLShop\Bootstrap())->boot($d);'
        . ' echo "LEBT hooks=" . count($d->events) . " log=" . count(\JTL\Shop::$log);');
    $output = [];
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' 2>&1', $output);

    return implode("\n", $output);
};

$out = $runBroken(static function (string $copy): void {
    unlink($copy . '/src/Search/SearchPageEnhancer.php');
});
$check('Datei fehlt: Shop läuft weiter, kein Hook, Logeintrag', $out === 'LEBT hooks=0 log=1');

$out = $runBroken(static function (string $copy): void {
    file_put_contents($copy . '/src/Search/SearchConfig.php', '<?php this is kaputt {');
});
$check('Datei mit Syntaxfehler: Shop läuft weiter, kein Hook, Logeintrag', $out === 'LEBT hooks=0 log=1');

// --- Admin-Tabs: Einbinden liegt im geschützten Block ---
foreach (['updates.php' => 'UpdateBridge.php', 'plugin-empfehlungen.php' => 'PluginCatalogProvider.php'] as $tab => $needed) {
    $source = (string) file_get_contents($pluginDir . '/adminmenu/' . $tab);
    $tryAt = strpos($source, 'try {');
    $requireAt = strpos($source, 'require_once');
    $check($tab . ': require_once erst im try-Block', $tryAt !== false && $requireAt !== false && $requireAt > $tryAt);
    $check($tab . ': prüft vorher mit is_file', str_contains($source, 'is_file(') && str_contains($source, $needed));
}

echo "\nSafetyNet: " . $total . ' Prüfungen, ' . $failed . " Fehler\n";
exit($failed > 0 ? 1 : 0);
