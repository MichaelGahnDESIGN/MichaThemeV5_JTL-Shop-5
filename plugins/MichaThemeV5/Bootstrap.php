<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5;

use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use JTL\Smarty\JTLSmarty;
use Plugin\MichaThemeV5\Kit\Platform\Jtl\Setup;
use Plugin\MichaThemeV5\Support\KitSetup;

/**
 * Startpunkt. Im Shop (Frontend) macht das Plugin nichts außer die Bridge bereitzustellen,
 * die das Template per class_exists erkennt. Alles andere läuft nur im Admin (adminmenu/*.php).
 */
final class Bootstrap extends Bootstrapper
{
    public const BACKEND_EVENT = 'backend.notification';

    private static bool $pinged = false;

    public function boot(Dispatcher $dispatcher): void
    {
        parent::boot($dispatcher);
        require_once __DIR__ . '/src/autoload.php';
        // Bridge laden, damit das Template sie mit class_exists(..., false) findet
        require_once __DIR__ . '/src/Bridge.php';
        // Aktiv-Meldung max 1x am Tag, nur Backend. backend.notification = Admin-Glocke, kommt nur im Admin (wie im JTL-Bootstrapper selbst)
        try {
            if (Setup::isBackend($_SERVER['SCRIPT_NAME'] ?? null)) {
                $dispatcher->listen(self::BACKEND_EVENT, function (): void {
                    if (self::$pinged) {
                        return;
                    }
                    self::$pinged = true;
                    $this->report(false);
                });
            }
        } catch (\Throwable) {
            // Hook geht nicht, egal
        }
    }

    public function installed(): void
    {
        parent::installed();
        $this->meldenSofort();
    }

    public function enabled(): void
    {
        parent::enabled();
        $this->meldenSofort();
    }

    public function updated($oldVersion, $newVersion): void
    {
        parent::updated($oldVersion, $newVersion);
        // neue Version mitgeben, getMeta() kann hier noch die alte kennen
        $this->report(true, ['version' => (string) $newVersion]);
    }

    // abmelden, Eintrag auf theme.michael-gahn.de wird gelöscht. Wirft nie, Deinstallieren läuft immer durch
    public function uninstalled(bool $deleteData = true): void
    {
        try {
            require_once __DIR__ . '/src/autoload.php';
            KitSetup::forShop($this->getPlugin())?->uninstall($deleteData);
        } catch (\Throwable) {
            // still
        }
        parent::uninstalled($deleteData);
    }

    // installed/enabled: sofort melden (Baukasten), Installation darf nie dran scheitern
    private function meldenSofort(): void
    {
        try {
            require_once __DIR__ . '/src/autoload.php';
            Setup::forShop($this->getPlugin(), KitSetup::options())?->ping(true);
        } catch (\Throwable) {
            // still
        }
    }

    // Rückfall: Reiter vom Baukasten, falls JTL die Tabs nicht über adminmenu/*.php holt
    public function renderAdminMenuTab(string $tabName, int $menuID, JTLSmarty $smarty): string
    {
        try {
            require_once __DIR__ . '/src/autoload.php';

            return Setup::renderTab(KitSetup::forShop($this->getPlugin()), $tabName) ?? parent::renderAdminMenuTab($tabName, $menuID, $smarty);
        } catch (\Throwable) {
            return parent::renderAdminMenuTab($tabName, $menuID, $smarty);
        }
    }

    // Aktiv-Meldung über den Baukasten (Kit/), Installation darf nie dran scheitern
    private function report(bool $force, array $extra = []): void
    {
        try {
            require_once __DIR__ . '/src/autoload.php';
            KitSetup::forShop($this->getPlugin(), $extra)?->ping($force);
        } catch (\Throwable) {
            // still
        }
    }
}
