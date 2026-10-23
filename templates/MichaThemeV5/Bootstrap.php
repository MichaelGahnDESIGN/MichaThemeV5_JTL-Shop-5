<?php

declare(strict_types=1);

namespace Template\MichaThemeV5;

use JTL\Shop;
use Template\NOVA\Bootstrap as NovaBootstrap;

require_once __DIR__ . '/php/LicenseClient.php';

/**
 * Stellt dem Template den Lizenzstatus als Variable $mtLicense bereit (valid, tier, modules, reason).
 * Das Theme selbst wertet ihn nicht aus; ein separat ausgeliefertes Add-on kann damit Erweiterungen freischalten.
 * Ist dieser Einstiegspunkt nicht verfügbar oder schlägt er fehl, fehlt die Variable und es gilt Free.
 *
 * Wichtig (JTL-Shop 5.8): boot() läuft mitten in JTLSmarty::initTemplate(). Hier darf daher weder Shop::Smarty()
 * noch etwas anderes aufgerufen werden, das das aktive Template lädt (Endlosrekursion). Die Smarty-Instanz
 * kommt über $this->getSmarty().
 * JTL startet pro Template nur EINEN Bootstrapper (den des Kind-Templates). Damit die Smarty-Plugins von NOVA
 * (get_navigation, trans, ...) weiter registriert werden, erbt diese Klasse von Template\NOVA\Bootstrap und
 * ruft parent::boot() auf.
 */
class Bootstrap extends NovaBootstrap
{
    public function boot(): void
    {
        parent::boot();
        $smarty = $this->getSmarty();
        if ($smarty === null) {
            return;
        }
        $license = [];
        try {
            $client = new \MichaThemeV5\Client\LicenseClient(
                getenv('MT_LICENSE_API') ?: 'https://theme.michael-gahn.de/api',
                (string) parse_url(Shop::getURL(), PHP_URL_HOST),
                static function (string $url): ?array {
                    $ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
                    $body = @file_get_contents($url, false, $ctx);
                    if ($body === false) {
                        return null;
                    }
                    $status = 0;
                    foreach ($http_response_header ?? [] as $h) {
                        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
                            $status = (int) $m[1];
                        }
                    }
                    $json = json_decode($body, true);

                    return ['status' => $status, 'json' => is_array($json) ? $json : []];
                },
                fn (string $k): ?array => $this->getCache()->get($k) ?: null,
                function (string $k, array $v, int $ttl): void {
                    $this->getCache()->set($k, $v, ['mt_license'], $ttl);
                }
            );
            $license = $client->state($this->readLicenseToken());
        } catch (\Throwable) {
            $license = [];
        }
        $smarty->assign('mtLicense', $license);
        // Das Theme greift nur auf die ausdrücklich vom installierten Plugin freigegebenen Daten zu.
        // Ohne Plugin bleibt die Glocke leer; es gibt keinen Fallback mit Kundendaten im Template.
        $notifications = ['public' => [], 'personal' => []];
        if (class_exists('Plugin\\MichaThemeV5NotificationCenter\\NotificationBridge')) {
            try {
                $customerId = (int) (Shop::getCustomer()->kKunde ?? 0);
                $notifications = \Plugin\MichaThemeV5NotificationCenter\NotificationBridge::forTemplate($this->getDB(), $customerId);
            } catch (\Throwable) {
                $notifications = ['public' => [], 'personal' => []];
            }
        }
        $smarty->assign('mtNotifications', $notifications);
    }

    /**
     * Wird von JTL beim Aktivieren des Templates im Admin aufgerufen. Ein Kind-Template von NOVA braucht einen
     * gespeicherten Wert theme_default (Abschnitt "theme"), sonst bricht JTL beim Laden der Pfade ab
     * (Paths::$realRelThemeDir nicht initialisiert). Ist keiner gespeichert, wird der NOVA-Standard "clear" eingetragen.
     */
    public function enabled(): void
    {
        parent::enabled();
        $config = new \JTL\Template\Config('MichaThemeV5', $this->getDB());
        if (($config->loadConfigFromDB()['theme']['theme_default'] ?? '') === '') {
            $config->updateConfigInDB('theme', 'theme_default', 'clear');
        }
    }

    /**
     * Liest den Lizenzschlüssel direkt aus den Template-Einstellungen in der Datenbank
     * (lädt das Template-Modell nicht, daher rekursionsfrei).
     */
    private function readLicenseToken(): string
    {
        $row = $this->getDB()->getSingleObject(
            'SELECT cWert FROM ttemplateeinstellungen WHERE cTemplate = :t AND cName = :n',
            ['t' => 'MichaThemeV5', 'n' => 'mt_license_token']
        );

        return (string) ($row->cWert ?? '');
    }
}
