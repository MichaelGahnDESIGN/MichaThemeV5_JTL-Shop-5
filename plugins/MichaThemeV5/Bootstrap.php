<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5;

use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;

/**
 * Startpunkt. Im Shop (Frontend) macht das Plugin nichts außer die Bridge bereitzustellen,
 * die das Template per class_exists erkennt. Alles andere läuft nur im Admin (adminmenu/*.php).
 */
final class Bootstrap extends Bootstrapper
{
    public function boot(Dispatcher $dispatcher): void
    {
        parent::boot($dispatcher);
        require_once __DIR__ . '/src/autoload.php';
        // Bridge laden, damit das Template sie mit class_exists(..., false) findet
        require_once __DIR__ . '/src/Bridge.php';
    }
}
