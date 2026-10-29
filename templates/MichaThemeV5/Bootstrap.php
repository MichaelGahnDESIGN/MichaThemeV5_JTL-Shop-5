<?php

declare(strict_types=1);

namespace Template\MichaThemeV5;

use JTL\Shop;
use Template\NOVA\Bootstrap as NovaBootstrap;

require_once __DIR__ . '/php/LicenseClient.php';
// Kernklassen für eigene Farben, auch ohne Plugin (das Plugin bringt dieselben mit, wer zuerst da ist gewinnt)
foreach (['Sanitizer', 'ColorOverrides'] as $mtCore) {
    if (!class_exists('MichaThemeV5\\Plugin\\' . $mtCore, false)) {
        require_once __DIR__ . '/php/mt-core/' . $mtCore . '.php';
    }
}
unset($mtCore);

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
        $this->ensureNewDefaults();
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
        // Eigene Farben als CSS-Block (leer = nichts ändern), Plugin-Hinweis nur zur Info
        $smarty->assign('mtColorCss', $this->colorCss());
        $smarty->assign('mtPluginActive', class_exists('Plugin\\MichaThemeV5\\Bridge', false));
        // Glocke nur, wenn das Plugin aktiv ist UND seine Tabellen da sind (sonst wäre sie leer)
        // Das Theme greift nur auf die ausdrücklich vom Plugin freigegebenen Daten zu, kein Fallback mit Kundendaten
        $notifications = ['public' => [], 'personal' => []];
        $available = false;
        if (class_exists('Plugin\\MichaThemeV5NotificationCenter\\NotificationBridge', false)) {
            try {
                // JTL schluckt SQL-Fehler (nur Log), deshalb Tabellen vorher prüfen; Treffer kurz im Cache
                $ready = $this->getCache()->get('mt_notification_tables') === true;
                if (!$ready) {
                    $ready = $this->getDB()->tableExists('xplugin_mgd_notifications')
                        && $this->getDB()->tableExists('xplugin_mgd_notification_recipients');
                    if ($ready) {
                        $this->getCache()->set('mt_notification_tables', true, ['mt_notification'], 600);
                    }
                }
                if ($ready) {
                    $customerId = (int) (Shop::getCustomer()->kKunde ?? 0);
                    $notifications = \Plugin\MichaThemeV5NotificationCenter\NotificationBridge::forTemplate($this->getDB(), $customerId);
                    $available = true;
                }
            } catch (\Throwable) {
                $notifications = ['public' => [], 'personal' => []];
            }
        }
        $smarty->assign('mtNotifications', $notifications);
        $smarty->assign('mtNotificationsAvailable', $available);
    }

    /**
     * Neue Optionen haben nach einem Update noch keinen Wert in der Datenbank (JTL legt Standardwerte nur beim
     * Aktivieren oder Speichern an). Fehlt ein Wert, kommt der Standard aus template.xml hinein (einmalig je
     * Version, danach nur ein Cache-Treffer). Ohne das wären z. B. Herz und Favoriten-Icon nach dem Update weg.
     */
    private function ensureNewDefaults(): void
    {
        try {
            $xml = @simplexml_load_file(__DIR__ . '/template.xml');
            $version = $xml !== false ? (string) $xml->Version : '';
            $flag = 'mt_defaults_' . md5($version);
            if ($xml === false || $this->getCache()->get($flag) === true) {
                return;
            }
            $config  = new \JTL\Template\Config('MichaThemeV5', $this->getDB());
            $saved   = $config->loadConfigFromDB();
            $changed = false;
            foreach ($xml->Settings->Section as $section) {
                $key = (string) $section['Key'];
                // nur unsere Abschnitte, nicht Theme/Header von NOVA, und nicht den reinen Hinweis
                if (!str_starts_with($key, 'mt_') || $key === 'mt_hint') {
                    continue;
                }
                foreach ($section->Setting as $setting) {
                    $name = (string) $setting['Key'];
                    if (!isset($saved[$key][$name])) {
                        $config->updateConfigInDB($key, $name, (string) $setting['Value']);
                        $changed = true;
                    }
                }
            }
            if ($changed) {
                $this->getCache()->flushTags([\CACHING_GROUP_TEMPLATE, \CACHING_GROUP_OPTION]);
            }
            $this->getCache()->set($flag, true, ['mt_notification'], 3600);
        } catch (\Throwable) {
            // kein Abbruch, die Templates haben eigene Rückfallwerte (|default)
        }
    }

    /**
     * Eigene Farben (Plugin oder Template-Einstellungen, gleiche Schlüssel) als CSS-Variablen im <head>.
     * Wird zwischengespeichert und beim Speichern der Einstellungen automatisch geleert. Nur geprüfte Farbwerte.
     */
    private function colorCss(): string
    {
        try {
            $hit = $this->getCache()->get('mt_color_css');
            if (is_string($hit)) {
                return $hit;
            }
            $options = json_decode((string) file_get_contents(__DIR__ . '/mt/options.json'), true)['options'] ?? [];
            $saved   = (new \JTL\Template\Config('MichaThemeV5', $this->getDB()))->loadConfigFromDB();
            $entries = [];
            foreach ($options as $o) {
                if (!isset($o['color'])) {
                    continue;
                }
                $v = $saved['mt_' . $o['group']]['mt_' . $o['id']] ?? '';
                if (is_string($v) && $v !== '') {
                    $entries[] = ['token' => $o['color']['token'], 'mode' => $o['color']['mode'], 'value' => $v];
                }
            }
            $css = \MichaThemeV5\Plugin\ColorOverrides::css($entries, $saved['mt_colors']['mt_accent_custom'] ?? '');
            $this->getCache()->set('mt_color_css', $css, [\CACHING_GROUP_TEMPLATE, \CACHING_GROUP_OPTION], 86400);

            return $css;
        } catch (\Throwable) {
            return '';
        }
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
