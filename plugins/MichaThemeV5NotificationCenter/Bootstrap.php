<?php
declare(strict_types=1);
namespace Plugin\MichaThemeV5NotificationCenter;

use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;

/** Registriert das Paket. Geschäftslogik liegt in der Bridge und nie im Theme. */
final class Bootstrap extends Bootstrapper
{
    public function boot(Dispatcher $dispatcher): void
    {
        parent::boot($dispatcher);
        require_once __DIR__ . '/src/NotificationBridge.php';
    }
}
