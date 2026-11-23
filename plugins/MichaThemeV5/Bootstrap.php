<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5;

use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use JTL\Shop;
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
            if (!Shop::isFrontend()) {
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
        $this->report(true);
    }

    public function enabled(): void
    {
        parent::enabled();
        $this->report(true);
    }

    public function updated($oldVersion, $newVersion): void
    {
        parent::updated($oldVersion, $newVersion);
        $this->report(true);
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

    // Aktiv-Meldung über den Baukasten (Kit/), Installation darf nie dran scheitern
    private function report(bool $force): void
    {
        try {
            require_once __DIR__ . '/src/autoload.php';
            KitSetup::forShop($this->getPlugin())?->ping($force);
        } catch (\Throwable) {
            // still
        }
    }
}
