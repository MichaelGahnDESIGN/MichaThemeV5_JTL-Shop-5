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
