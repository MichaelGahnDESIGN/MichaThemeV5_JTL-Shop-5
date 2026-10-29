<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Admin;

use MichaThemeV5\Plugin\FieldRenderer;
use Plugin\MichaThemeV5\Settings\TemplateStore;
use Plugin\MichaThemeV5\Support\Perms;

defined('PFAD_ROOT') || exit;

/** Tab "Einstellungen": Presets, Gruppen mit Suche, Farben (Colorpicker, Kontrast-Warnung), Layout. */
final class SettingsPage extends Page
{
    public function tabKey(): string
    {
        return 'settings';
    }

    public function render(): string
    {
        $store = TemplateStore::forShop();
        $this->handle($store);
        $out = $this->assets() . $this->notice();

        if (!TemplateStore::templateInstalled()) {
            return $out . '<div class="mtadm mtadm-page"><section class="mtadm-card"><h2>Das Template MichaThemeV5 ist nicht installiert</h2>'
                . '<p>Die Einstellungen gehören zum Template. Bitte richten Sie das Template zuerst im Tab „Info“ ein oder kopieren Sie den Ordner von Hand (siehe Anleitung).</p>'
                . '<p><a class="mtadm-primary" href="' . $this->e($this->url('info')) . '">Zum Tab Info</a></p></section></div>';
        }
        if (!Perms::settings()) {
            return $out . '<div class="mtadm mtadm-page"><section class="mtadm-card"><h2>Keine Berechtigung</h2><p>Für die Einstellungen brauchen Sie das Recht für die Seite Darstellung › Template.</p></section></div>';
        }
        $out .= $this->adjusted($store);
        $presetForm = $this->formOpen('apply_preset');

        return $out . FieldRenderer::settings($store->schema(), $store->values(), [
            'formOpen' => $this->formOpen('save_settings'),
            'formClose' => FieldRenderer::saveBar('Änderungen speichern') . '</form>',
            'paletteJson' => (string) json_encode($store->palette()),
            'presetFormOpen' => $presetForm,
            'mediaButtons' => false,
            'hiddenGroups' => ['license', 'advanced'],
        ]);
    }

    private function handle(TemplateStore $store): void
    {
        $action = $this->postedAction();
        if (!in_array($action, ['save_settings', 'apply_preset'], true)) {
            return;
        }
        if (($err = $this->guard(Perms::settings())) !== null) {
            $this->back($err);

            return;
        }
        if (!TemplateStore::templateInstalled()) {
            $this->back('no_template');

            return;
        }
        if ($action === 'apply_preset') {
            $id = isset($_POST['mt_preset']) && is_string($_POST['mt_preset']) && preg_match('/^[a-z0-9_-]{1,40}$/', $_POST['mt_preset']) === 1 ? $_POST['mt_preset'] : '';
            $this->back($store->applyPreset($id) < 0 ? 'preset_bad' : 'preset_ok');

            return;
        }
        // Formular: nur flache Werte (Text, Zahl), nichts Verschachteltes
        $input = [];
        $raw = isset($_POST['mt']) && is_array($_POST['mt']) ? $_POST['mt'] : [];
        foreach ($raw as $k => $v) {
            if (is_string($k) && (is_string($v) || is_int($v))) {
                $input[$k] = $v;
            }
        }
        $res = $store->save($input);
        if (isset($_POST['mt_palette']) && is_string($_POST['mt_palette']) && strlen($_POST['mt_palette']) < 4096) {
            $store->savePalette($_POST['mt_palette']);
        }
        if ($res['adjusted']) {
            $_SESSION['mt_adjusted'] = $res['adjusted'];
        }
        $n = count($res['changed']);
        $this->back($n > 0 ? 'saved' : 'unchanged', $n > 0 ? ['n' => $n] : []);
    }

    /** Hinweis auf Felder, deren Wert ungültig war und geleert wurde (einmalig). */
    private function adjusted(TemplateStore $store): string
    {
        $ids = $_SESSION['mt_adjusted'] ?? null;
        unset($_SESSION['mt_adjusted']);
        if (!is_array($ids) || !$ids) {
            return '';
        }
        $opts = $store->options();
        $h = '<div class="mtadm"><div class="mtadm-errors" role="alert"><h2>Diese Werte waren ungültig und wurden geleert</h2><ul>';
        foreach ($ids as $id) {
            if (is_string($id) && isset($opts[$id])) {
                $h .= '<li><a href="#mtf-' . $this->e($id) . '">' . $this->e($opts[$id]['label']) . '</a></li>';
            }
        }

        return $h . '</ul></div></div>';
    }
}
