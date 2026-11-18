<?php
declare(strict_types=1);
namespace Plugin\MichaThemeV5NotificationCenter;

use JTL\Events\Dispatcher;
use JTL\Helpers\Form;
use JTL\Plugin\Bootstrapper;
use JTL\Session\Frontend;
use JTL\Shop;
use Plugin\MichaThemeV5NotificationCenter\Update\UpdateBridge;

/** Registriert das Paket. Geschäftslogik liegt in der Bridge und nie im Theme. */
final class Bootstrap extends Bootstrapper
{
    public function boot(Dispatcher $dispatcher): void
    {
        parent::boot($dispatcher);
        // Theme prüft class_exists(..., false), darum hier immer laden
        require_once __DIR__ . '/src/NotificationBridge.php';
        require_once __DIR__ . '/src/NotificationActions.php';
        try {
            $config = $this->getPlugin()->getConfig();
            NotificationBridge::configure((int) ($config->getValue('mgd_nc_visible_limit') ?: 5), (string) ($config->getValue('mgd_nc_icon') ?: 'bell'));
        } catch (\Throwable) {
            // Standardwerte bleiben
        }
        // Free-Lizenz: im Admin max 1x am Tag melden, nie im Shop-Frontend
        if (!Shop::isFrontend() && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) === 'GET') {
            $this->report(false);
        }
        // Gelesen/Ausblenden übers JTL-IO, nur mit gültigem Token
        $dispatcher->listen('shop.hook.' . \HOOK_IO_HANDLE_REQUEST, static function (array $args): void {
            // nicht doppelt anmelden, sonst wirft JTL eine Exception
            if ($args['io']->exists('mgdNotificationAction')) {
                return;
            }
            $args['io']->register('mgdNotificationAction', static function (string $action = '', int|string $id = 0, string $token = ''): array {
                if (!Form::validateToken($token)) {
                    return ['success' => false, 'error' => 'token'];
                }
                $customerId = (int) (Frontend::getCustomer()->getID() ?? 0);
                $count = NotificationActions::handle(Shop::Container()->getDB(), $customerId, $action, (int) $id);

                return $count === null ? ['success' => false] : ['success' => true, 'count' => $count];
            });
        });
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

    // Anmeldung theme.michael-gahn.de, darf nie was kaputt machen
    private function report(bool $force): void
    {
        try {
            require_once __DIR__ . '/src/Update/UpdateBridge.php';
            UpdateBridge::ping($this->getPlugin(), __DIR__, $force);
        } catch (\Throwable) {
            // egal, Plugin läuft weiter
        }
    }
}
