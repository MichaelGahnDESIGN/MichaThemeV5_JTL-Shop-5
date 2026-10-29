<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Admin;

use JTL\Plugin\PluginInterface;
use JTL\Shop;
use MichaThemeV5\Plugin\FieldRenderer;
use Plugin\MichaThemeV5\Support\{Perms, Redirect};

defined('PFAD_ROOT') || exit;

/**
 * Gemeinsamer Rahmen der vier Admin-Seiten. JTL rendert alle Tabs bei jedem Aufruf der Plugin-Seite,
 * deshalb gilt: Formulare tragen "mt_action" + "cPluginTab", jede Seite reagiert nur auf ihre eigenen Aktionen,
 * und Meldungen erscheinen nur im Tab, der gemeint ist. Meldungen kommen aus einer festen Liste (nie aus der URL).
 */
abstract class Page
{
    public const TABS = ['settings' => 'Einstellungen', 'license' => 'Lizenzen', 'catalog' => 'Plugin-Empfehlungen', 'info' => 'Info'];

    public const MESSAGES = [
        'saved' => ['ok', 'Einstellungen gespeichert.'],
        'unchanged' => ['ok', 'Es gab keine Änderungen zu speichern.'],
        'preset_ok' => ['ok', 'Preset angewendet. Mit dem Preset „Standard“ oder auf der Info-Seite („Einstellungen wiederherstellen“) geht es zurück.'],
        'preset_bad' => ['err', 'Dieses Preset gibt es nicht.'],
        'restored' => ['ok', 'Einstellungen aus der Sicherung wiederhergestellt.'],
        'no_backup' => ['err', 'Es gibt keine Sicherung zum Wiederherstellen.'],
        'lic_saved' => ['ok', 'Lizenzcode gespeichert und geprüft.'],
        'lic_format' => ['err', 'Der Lizenzcode sieht nicht richtig aus. Er besteht aus drei Teilen mit Punkt. Bitte vollständig aus dem Kundenkonto kopieren.'],
        'lic_removed' => ['ok', 'Lizenzcode entfernt. Das Template läuft mit allen Free-Funktionen.'],
        'lic_checked' => ['ok', 'Lizenz neu geprüft.'],
        'lic_wait' => ['err', 'Bitte warten Sie eine Minute, bevor Sie erneut prüfen.'],
        'template_copied' => ['ok', 'Template kopiert. Aktiviert wird es von Hand unter Darstellung › Template.'],
        'need_confirm' => ['err', 'Bitte bestätigen Sie zuerst, dass Sie ein Backup haben.'],
        'no_right' => ['err', 'Dafür fehlt Ihnen das Recht.'],
        'bad_token' => ['err', 'Die Sitzung ist abgelaufen oder das Formular war ungültig. Bitte versuchen Sie es noch einmal.'],
        'no_template' => ['err', 'Das Template MichaThemeV5 ist nicht installiert. Bitte zuerst auf der Info-Seite einrichten.'],
    ];

    /** Pro Aufruf Scripts und Styles nur einmal ausgeben (JTL rendert vier Tabs nacheinander). */
    private static bool $assetsDone = false;

    public function __construct(protected readonly PluginInterface $plugin)
    {
    }

    abstract public function tabKey(): string;

    public function tabName(): string
    {
        return self::TABS[$this->tabKey()];
    }

    protected function e(mixed $s): string
    {
        return FieldRenderer::e($s);
    }

    /** Adresse der Plugin-Seite im Admin, optional mit Parametern. */
    public function url(string $tab, array $args = []): string
    {
        $base = Shop::getAdminURL() . '/plugin/' . $this->plugin->getID();

        return $base . '?' . http_build_query(['cPluginTab' => self::TABS[$tab]] + $args);
    }

    protected function postedAction(): string
    {
        if (!Perms::post() || !isset($_POST['mt_action']) || !is_string($_POST['mt_action'])) {
            return '';
        }

        return preg_match('/^[a-z_]{1,30}$/', $_POST['mt_action']) === 1 ? $_POST['mt_action'] : '';
    }

    /** Formular-Kopf: POST an die Plugin-Seite, Aktion, Tab und JTL-Token. */
    protected function formOpen(string $action, string $extra = ''): string
    {
        return '<form method="post" action="' . $this->e(Shop::getAdminURL() . '/plugin/' . $this->plugin->getID()) . '">'
            . '<input type="hidden" name="mt_action" value="' . $this->e($action) . '">'
            . '<input type="hidden" name="cPluginTab" value="' . $this->e($this->tabName()) . '">'
            . Perms::tokenInput() . $extra;
    }

    /** Antwort nach einer Aktion: zurück zur Seite (Neuladen löst die Aktion nicht nochmal aus). */
    protected function back(string $msg, array $args = []): void
    {
        $url = $this->url($this->tabKey(), ['mt_msg' => $msg] + $args);
        if (Redirect::to($url)) {
            return;
        }
        // Ausnahme: Kopf schon gesendet, dann die Meldung direkt zeigen
        $_GET['mt_msg'] = $msg;
        $_GET['cPluginTab'] = $this->tabName();
        foreach ($args as $k => $v) {
            $_GET[$k] = (string) $v;
        }
    }

    /** Prüft Methode, Token und Recht. null = erlaubt, sonst der Schlüssel der Fehlermeldung. */
    protected function guard(bool $allowed): ?string
    {
        if (!Perms::post() || !Perms::token()) {
            return 'bad_token';
        }

        return $allowed ? null : 'no_right';
    }

    /** Meldung für diesen Tab (nur wenn der Tab gemeint ist). */
    protected function notice(): string
    {
        $tab = isset($_GET['cPluginTab']) && is_string($_GET['cPluginTab']) ? $_GET['cPluginTab'] : '';
        $msg = isset($_GET['mt_msg']) && is_string($_GET['mt_msg']) ? $_GET['mt_msg'] : '';
        if ($tab !== $this->tabName() || !isset(self::MESSAGES[$msg])) {
            return '';
        }
        [$kind, $text] = self::MESSAGES[$msg];
        if ($msg === 'saved' && isset($_GET['n']) && is_string($_GET['n']) && ctype_digit($_GET['n'])) {
            $n = (int) $_GET['n'];
            $text = $n === 1 ? '1 Einstellung gespeichert.' : $n . ' Einstellungen gespeichert.';
        }

        return '<div class="mtadm mtadm-page"><p class="mtadm-note mtadm-note--' . ($kind === 'ok' ? 'ok' : 'err') . '" role="' . ($kind === 'ok' ? 'status' : 'alert') . '">'
            . $this->e(($kind === 'ok' ? 'Erledigt: ' : 'Achtung: ') . $text) . '</p></div>';
    }

    /** CSS und JS einmal pro Aufruf, alles lokal aus dem Plugin-Ordner. */
    protected function assets(): string
    {
        if (self::$assetsDone) {
            return '';
        }
        self::$assetsDone = true;
        $u = $this->plugin->getPaths()->getAdminURL();
        $v = rawurlencode((string) (require dirname(__DIR__) . '/Generated/version.php')['version']);

        return '<link rel="stylesheet" href="' . $this->e($u . 'mt-colorpicker.css?v=' . $v) . '">'
            . '<link rel="stylesheet" href="' . $this->e($u . 'mt-admin.css?v=' . $v) . '">'
            . '<link rel="stylesheet" href="' . $this->e($u . 'mt-admin-jtl.css?v=' . $v) . '">'
            . '<script src="' . $this->e($u . 'mt-colorpicker.js?v=' . $v) . '" defer></script>'
            . '<script src="' . $this->e($u . 'mt-admin-logic.js?v=' . $v) . '" defer></script>'
            . '<script src="' . $this->e($u . 'mt-admin.js?v=' . $v) . '" defer></script>';
    }

    /** Fertige Ausgabe eines Tabs. */
    abstract public function render(): string;
}
