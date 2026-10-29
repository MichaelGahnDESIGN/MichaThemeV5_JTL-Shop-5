<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Admin;

use JTL\Shop;
use MichaThemeV5\Plugin\PageRenderer;
use Plugin\MichaThemeV5\Bridge;
use Plugin\MichaThemeV5\License\LicenseService;
use Plugin\MichaThemeV5\Settings\TemplateStore;
use Plugin\MichaThemeV5\Support\Perms;
use Plugin\MichaThemeV5\Theme\TemplateInstaller;

defined('PFAD_ROOT') || exit;

/** Tab "Info": Status, Template einrichten (mit Rückfrage), Sicherung, Systeminfo, Changelog. */
final class InfoPage extends Page
{
    private ?string $confirm = null;

    public function tabKey(): string
    {
        return 'info';
    }

    private function installer(): TemplateInstaller
    {
        return new TemplateInstaller(PFAD_ROOT, $this->plugin->getPaths()->getBasePath());
    }

    public function render(): string
    {
        $store = TemplateStore::forShop();
        $inst = $this->installer();
        $this->handle($store, $inst);
        if ($this->confirm !== null) {
            return $this->assets() . $this->confirm;
        }
        $version = (string) (require dirname(__DIR__) . '/Generated/version.php')['version'];
        $installed = $inst->installedVersion();
        $active = $installed !== null && $store->templateActive();
        $lic = LicenseService::forShop()->state();
        $reason = (string) ($lic['reason'] ?? '');
        $fits = $installed !== null && version_compare($installed, Bridge::TEMPLATE_MIN, '>=');
        $rows = [
            ['label' => 'Plugin-Version', 'value' => $version, 'state' => 'info'],
            ['label' => 'Template installiert', 'value' => $installed === null ? 'Nein. Bitte unten einrichten.' : 'Ja, Version ' . $installed, 'state' => $installed === null ? 'warn' : 'ok'],
            ['label' => 'Template aktiv', 'value' => $active ? 'Ja' : 'Nein (Aktivieren unter Darstellung › Template)', 'state' => $active ? 'ok' : 'warn'],
            ['label' => 'Template und Plugin passen zusammen', 'value' => $installed === null ? 'Nicht prüfbar (Template fehlt)' : ($fits ? 'Ja' : 'Nein, das Template ist älter als ' . Bridge::TEMPLATE_MIN), 'state' => $fits ? 'ok' : 'warn'],
            ['label' => 'Basis-Template NOVA', 'value' => $inst->novaPresent() ? 'Vorhanden' : 'Fehlt', 'state' => $inst->novaPresent() ? 'ok' : 'warn'],
            ['label' => 'Ordner templates/ beschreibbar', 'value' => $inst->templatesWritable() ? 'Ja' : 'Nein (Template muss von Hand kopiert werden)', 'state' => $inst->templatesWritable() ? 'ok' : 'warn'],
            ['label' => 'Lizenzserver', 'value' => $reason === 'unreachable' ? 'Gerade nicht erreichbar (Free bleibt aktiv)' : 'Erreichbar oder nicht nötig', 'state' => $reason === 'unreachable' ? 'warn' : 'ok'],
            ['label' => 'Notification Center', 'value' => class_exists('Plugin\\MichaThemeV5NotificationCenter\\NotificationBridge', false) ? 'Plugin ist aktiv' : 'Plugin nicht aktiv (optional)', 'state' => 'info'],
            ['label' => 'JTL-Shop / PHP', 'value' => (defined('APPLICATION_VERSION') ? APPLICATION_VERSION : '?') . ' / ' . PHP_VERSION, 'state' => 'info'],
        ];
        $changelogFile = rtrim($this->plugin->getPaths()->getBasePath(), '/\\') . '/CHANGELOG.md';
        $changelog = is_readable($changelogFile) ? (string) file_get_contents($changelogFile, false, null, 0, 200000) : '';
        $sys = 'MichaThemeV5 Plugin ' . $version . "\nTemplate: " . ($installed ?? 'nicht installiert') . "\nJTL-Shop " . (defined('APPLICATION_VERSION') ? APPLICATION_VERSION : '?')
            . "\nPHP " . PHP_VERSION . "\nLizenz: " . (($lic['valid'] ?? false) ? 'gültig (' . (string) ($lic['tier'] ?? '') . ')' : 'Free (' . ($reason ?: '-') . ')');
        $extra = $this->installerCard($inst, $installed, $active) . $this->restoreCard($store)
            . '<section class="mtadm-card" aria-labelledby="mti-sys"><h2 id="mti-sys">Systeminfo (ohne Lizenzcode)</h2><p class="mtadm-help">Für Support-Anfragen kopieren.</p>'
            . '<label class="mtadm-sr" for="mti-sysbox">Systeminfo zum Kopieren</label><textarea id="mti-sysbox" class="mtadm-sysbox" readonly rows="6">' . $this->e($sys) . '</textarea></section>';

        return $this->assets() . $this->notice() . PageRenderer::info($rows, [
            'extra' => $extra,
            'links' => [
                ['label' => 'Anleitung und Quellcode auf GitHub', 'url' => 'https://github.com/MichaelGahnDESIGN/MichaThemeV5_JTL-Shop-5'],
                ['label' => 'Webseite', 'url' => LicensePage::SHOP_URL],
            ],
            'changelog' => $changelog,
        ]);
    }

    private function installerCard(TemplateInstaller $inst, ?string $installed, bool $active): string
    {
        $bundled = $inst->bundledVersion();
        $h = '<section class="mtadm-card" aria-labelledby="mti-tpl"><h2 id="mti-tpl">Template einrichten</h2>';
        if ($bundled === null) {
            return $h . '<p>In diesem Plugin ist kein Template enthalten. Laden Sie das komplette Plugin-ZIP von der Download-Seite oder kopieren Sie den Ordner <code>templates/MichaThemeV5</code> von Hand (siehe Anleitung).</p></section>';
        }
        $h .= '<p>Im Plugin enthaltenes Template: Version <strong>' . $this->e($bundled) . '</strong>. Installiert: <strong>' . $this->e($installed ?? 'nicht installiert') . '</strong>.' . ($active ? ' Es ist aktiv.' : '') . '</p>'
            . '<p>Es wird nichts automatisch kopiert. Ein bisheriges Template wird vorher als ZIP gesichert (Ordner <code>plugins/MichaThemeV5/backup</code>). Ihre Template-Einstellungen bleiben gespeichert. Aktiviert wird das Template nie automatisch: Das machen Sie selbst unter Darstellung › Template.</p>';
        $needsCopy = $installed === null || version_compare($installed, $bundled, '<');
        if (!Perms::files()) {
            return $h . '<p>Dafür brauchen Sie die Rechte des Hauptadministrators.</p></section>';
        }
        if (($err = $inst->precheck()) !== null) {
            return $h . '<p class="mtadm-note" role="status">' . $this->e($err) . '</p></section>';
        }
        if ($needsCopy) {
            $h .= $this->formOpen('template_copy', '<input type="hidden" name="mt_step" value="ask">')
                . '<label class="mtadm-check"><input type="checkbox" name="mt_confirm" value="1"> <span>Ich habe ein Backup meines Shops.</span></label>'
                . '<p class="mtadm-actions"><button type="submit" class="mtadm-primary">' . ($installed === null ? 'Template kopieren' : 'Template aktualisieren (kopieren)') . '</button></p></form>';
        } else {
            $h .= '<p><strong>Das Template ist auf dem neuesten Stand.</strong></p>';
        }
        if ($installed !== null && !$active) {
            $h .= '<p><a class="mtadm-link" href="' . $this->e(Shop::getAdminURL() . '/template') . '">Zu Darstellung › Template (dort aktivieren)</a></p>';
        }

        return $h . '</section>';
    }

    private function restoreCard(TemplateStore $store): string
    {
        $b = $store->backupInfo();
        $h = '<section class="mtadm-card" aria-labelledby="mti-bk"><h2 id="mti-bk">Einstellungen sichern und wiederherstellen</h2>';
        if ($b === null) {
            return $h . '<p>Noch keine Sicherung vorhanden. Vor jedem Speichern legt das Plugin automatisch eine an.</p></section>';
        }
        $h .= '<p>Letzte Sicherung: ' . $this->e(date('d.m.Y H:i', $b['at'])) . '. Beim Wiederherstellen werden die gesicherten Werte zurückgeschrieben.</p>';
        if (!Perms::license()) {
            return $h . '</section>';
        }

        return $h . $this->formOpen('restore_settings')
            . '<label class="mtadm-check"><input type="checkbox" name="mt_confirm" value="1"> <span>Ja, aktuelle Einstellungen durch die Sicherung ersetzen.</span></label>'
            . '<p class="mtadm-actions"><button type="submit" class="mtadm-btn">Einstellungen wiederherstellen</button></p></form></section>';
    }

    private function handle(TemplateStore $store, TemplateInstaller $inst): void
    {
        $action = $this->postedAction();
        if ($action === 'restore_settings') {
            if (($err = $this->guard(Perms::license())) !== null) {
                $this->back($err);

                return;
            }
            if (empty($_POST['mt_confirm'])) {
                $this->back('need_confirm');

                return;
            }
            $this->back($store->restore() > 0 ? 'restored' : 'no_backup');

            return;
        }
        if ($action !== 'template_copy') {
            return;
        }
        if (($err = $this->guard(Perms::files())) !== null) {
            $this->back($err);

            return;
        }
        if (empty($_POST['mt_confirm'])) {
            $this->back('need_confirm');

            return;
        }
        $step = isset($_POST['mt_step']) && is_string($_POST['mt_step']) ? $_POST['mt_step'] : '';
        if ($step !== 'do') {
            // Rückfrage als eigene Karte, ohne Weiterleitung
            $this->confirm = '<div class="mtadm mtadm-page"><section class="mtadm-card" aria-labelledby="mti-ask"><h2 id="mti-ask">Template kopieren?</h2>'
                . '<p>Das mitgelieferte Template (Version ' . $this->e($inst->bundledVersion() ?? '?') . ') wird in den Ordner <code>templates/MichaThemeV5</code> kopiert. Ein vorhandenes Template wird vorher gesichert und bei einem Fehler zurückgespielt. Wirklich fortfahren?</p>'
                . $this->formOpen('template_copy', '<input type="hidden" name="mt_step" value="do"><input type="hidden" name="mt_confirm" value="1">')
                . '<p class="mtadm-actions"><button type="submit" class="mtadm-primary">Ja, Template kopieren</button>'
                . '<a class="mtadm-btn" href="' . $this->e($this->url('info')) . '">Abbrechen</a></p></form></section></div>';

            return;
        }
        $res = $inst->install();
        if (!$res['ok']) {
            $this->confirm = '<div class="mtadm mtadm-page"><section class="mtadm-card mtadm-status mtadm-status--problem" aria-labelledby="mti-fail"><h2 id="mti-fail">Template kopieren fehlgeschlagen</h2>'
                . '<p role="alert">' . $this->e($res['message']) . '</p>'
                . '<p><a class="mtadm-link" href="' . $this->e($this->url('info')) . '">Zurück zum Tab Info</a></p></section></div>';

            return;
        }
        $this->afterInstall($inst);
        $this->back('template_copied');
    }

    /** Versionsnummer in ttemplate mit der template.xml abgleichen und Cache leeren. Aktiviert wird nichts. */
    private function afterInstall(TemplateInstaller $inst): void
    {
        try {
            $v = $inst->installedVersion();
            $db = Shop::Container()->getDB();
            if ($v !== null && $db->select('ttemplate', 'cTemplate', TemplateInstaller::NAME) !== null) {
                $db->update('ttemplate', 'cTemplate', TemplateInstaller::NAME, (object) ['version' => $v]);
            }
            Shop::Container()->getCache()->flushTags([\CACHING_GROUP_OPTION, \CACHING_GROUP_TEMPLATE]);
        } catch (\Throwable) {
            // Hinweis: die Dateien sind ersetzt, das reicht; die Versionsnummer holt sich JTL beim nächsten Speichern im Template-Menü
        }
    }
}
