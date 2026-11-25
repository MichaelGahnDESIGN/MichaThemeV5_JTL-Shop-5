<?php

declare(strict_types=1);

namespace Plugin\MGDSucheJTLShop;

use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use JTL\Shop;
use Plugin\MGDSucheJTLShop\Search\CharacteristicSuggestionProvider;
use Plugin\MGDSucheJTLShop\Search\SearchConfig;
use Plugin\MGDSucheJTLShop\Search\SearchPageEnhancer;
use Plugin\MGDSucheJTLShop\Search\SearchTermNormalizer;
use Plugin\MGDSucheJTLShop\Kit\Platform\Jtl\{JtlKit, Setup};

/**
 * Startpunkt des Plugins.
 *
 * Die Standardsuche von JTL bleibt bewusst unverändert. Das verhindert, dass
 * Preis-, Sichtbarkeits- oder Kundengruppenlogik des Shops umgangen wird. Das
 * Plugin ergänzt ausschließlich geprüfte Merkmal-Hinweise auf der Suchseite.
 */
final class Bootstrap extends Bootstrapper
{
    private const LOG_MESSAGE_LENGTH = 200;

    // Baukasten: alter Datenordner bleibt (Schalter + Anmelde-ID gehen sonst verloren)
    public const KIT_OPTIONS = ['dataFolder' => 'mgd_suche_update'];

    public function boot(Dispatcher $dispatcher): void
    {
        parent::boot($dispatcher);

        // Sicherheitsnetz: Fehler im Plugin schaltet nur das Plugin ab, nicht den Shop
        try {
            $this->start($dispatcher);
        } catch (\Throwable $e) {
            $this->logFailure($e);
        }
    }

    private function start(Dispatcher $dispatcher): void
    {
        $this->loadPluginClasses();

        // Im Backend wird keine Frontend-Logik und keine Produktdatenabfrage ausgeführt.
        if (Shop::isFrontend() === false) {
            // Aktiv-Meldung nur im Admin, max 1x am Tag (prüft der Install-Client selbst)
            if (Setup::isBackend()) {
                $this->kit()?->ping();
            }

            return;
        }

        $config = new SearchConfig($this->getPlugin());
        if ($config->isEnabled() === false) {
            return;
        }

        $provider = new CharacteristicSuggestionProvider(
            $this->getDB(),
            new SearchTermNormalizer(),
            $config,
            Shop::getLanguageID()
        );
        $enhancer = new SearchPageEnhancer(
            $this->getPlugin(),
            $provider,
            $config,
            function (\Throwable $e): void {
                $this->logFailure($e);
            }
        );

        // Dieser dokumentierte Hook arbeitet mit dem finalen Smarty-Dokument.
        // Dadurch bleibt das Plugin mit NOVA und Child-Templates kompatibel.
        $dispatcher->listen('shop.hook.' . \HOOK_SMARTY_OUTPUTFILTER, [$enhancer, 'inject']);
    }

    /**
     * JTL-Shop 5 registriert Plugin-Namespaces automatisch. Die explizite Liste
     * hält das Paket zusätzlich mit Installationen kompatibel, die diesen
     * Autoloader durch ein älteres Plugin-Setup eingeschränkt bereitstellen.
     */
    private function loadPluginClasses(): void
    {
        foreach ([
            'src/Search/SearchConfig.php',
            'src/Search/SearchTermNormalizer.php',
            'src/Search/CharacteristicSuggestionProvider.php',
            'src/Search/SearchPageEnhancer.php',
            'src/Catalog/PluginCatalogProvider.php',
        ] as $file) {
            $path = __DIR__ . '/' . $file;
            // fehlende Datei = fangbarer Fehler statt Fatal Error
            if (is_file($path) === false) {
                throw new \RuntimeException('Plugin-Datei fehlt: ' . $file);
            }
            require_once $path;
        }
    }

    public function installed(): void
    {
        parent::installed();
        $this->kit()?->ping(true);
    }

    public function enabled(): void
    {
        parent::enabled();
        $this->kit()?->ping(true);
    }

    /** @param mixed $oldVersion @param mixed $newVersion */
    public function updated($oldVersion, $newVersion): void
    {
        parent::updated($oldVersion, $newVersion);
        // JTL hat hier noch die alte Version drin > neue mitgeben
        $this->kit(is_string($newVersion) ? $newVersion : null)?->ping(true);
    }

    public function uninstalled(bool $deleteData = true): void
    {
        // abmelden bei theme.michael-gahn.de, darf nix kippen
        $this->kit()?->uninstall($deleteData);
        parent::uninstalled($deleteData);
    }

    // Tabs Übersicht + Weitere Plugins (Customlink-Dateien in adminmenu/ nutzen dasselbe)
    public function renderAdminMenuTab(string $tabName, int $menuID, $smarty): string
    {
        return Setup::renderTab($this->kit(), $tabName) ?? parent::renderAdminMenuTab($tabName, $menuID, $smarty);
    }

    // Baukasten holen, nie Exception nach außen (null = nix passiert)
    private function kit(?string $version = null): ?JtlKit
    {
        try {
            return Setup::forShop($this->getPlugin(), self::KIT_OPTIONS + ($version !== null ? ['version' => $version] : []));
        } catch (\Throwable $e) {
            $this->logFailure($e);

            return null;
        }
    }

    // Eintrag ins JTL Systemlog, darf selbst nie was kippen
    private function logFailure(\Throwable $e): void
    {
        try {
            Shop::Container()->getLogService()->warning(
                'MGDSucheJTLShop: ' . get_class($e) . ' in ' . basename($e->getFile()) . ':' . $e->getLine()
                . ' - ' . mb_substr($e->getMessage(), 0, self::LOG_MESSAGE_LENGTH, 'UTF-8')
            );
        } catch (\Throwable) {
            // kein Log möglich, egal
        }
    }
}
