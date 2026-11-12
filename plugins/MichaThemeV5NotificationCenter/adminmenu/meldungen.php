<?php

declare(strict_types=1);

use JTL\Helpers\Form;
use JTL\Plugin\PluginInterface;
use JTL\Shop;
use Plugin\MichaThemeV5NotificationCenter\NotificationAdmin;

if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);
    echo 'Dieser Bereich ist nur in der JTL-Administration verfügbar.';

    return;
}

require_once dirname(__DIR__) . '/src/NotificationAdmin.php';

// Meldungen schreiben, bearbeiten, archivieren
try {
    $e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $db = Shop::Container()->getDB();
    if (!$db->tableExists('xplugin_mgd_notifications')) {
        echo '<div class="alert alert-warning" role="alert">Die Tabellen des Plugins fehlen. Bitte das Plugin einmal deaktivieren und wieder aktivieren.</div>';

        return;
    }
    $admin = new NotificationAdmin($db);
    $allowed = (bool) Shop::Container()->getAdminAccount()->permission('PLUGIN_ADMIN_VIEW');
    if (!$allowed) {
        echo '<div class="alert alert-danger" role="alert">Keine Berechtigung für diesen Bereich.</div>';

        return;
    }
    // Reiter nach dem Absenden wieder offen
    $menuId = 0;
    foreach ($oPlugin->getAdminMenu()->getItems() as $item) {
        if (str_ends_with((string) ($item->file ?? ''), 'meldungen.php')) {
            $menuId = (int) ($item->id ?? 0);
        }
    }
    $hidden = Form::getTokenInput() . '<input type="hidden" name="kPluginAdminMenu" value="' . $menuId . '">';
    $msg = '';
    $edit = null;
    $form = [];
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['mgd_nc_action'])) {
        if (!Form::validateToken()) {
            $msg = '<div class="alert alert-danger">Sicherheits-Token ungültig. Bitte die Seite neu laden.</div>';
        } elseif ($_POST['mgd_nc_action'] === 'save') {
            $result = $admin->save($_POST);
            if ($result['ok']) {
                $msg = '<div class="alert alert-success">Meldung gespeichert.</div>';
                Shop::Container()->getCache()->flushTags([\CACHING_GROUP_TEMPLATE]);
            } else {
                $msg = '<div class="alert alert-danger">' . $e($result['error'] ?? 'Fehler') . '</div>';
                $form = $_POST;
            }
        } elseif ($_POST['mgd_nc_action'] === 'archive') {
            $msg = $admin->archive((int) ($_POST['id'] ?? 0)) ? '<div class="alert alert-success">Meldung archiviert.</div>' : '<div class="alert alert-warning">Meldung nicht gefunden.</div>';
            Shop::Container()->getCache()->flushTags([\CACHING_GROUP_TEMPLATE]);
        } elseif ($_POST['mgd_nc_action'] === 'edit') {
            $edit = $admin->find((int) ($_POST['id'] ?? 0));
        }
    }
    $v = $form ?: ($edit ?? []);
    // mit Sekunden, damit beim Speichern nix verloren geht
    $local = static fn (?string $utc): string => $utc ? date('Y-m-d\TH:i:s', (int) strtotime($utc . ' UTC')) : '';
    $customer = $form['customer'] ?? (!empty($edit['customer_id']) ? (string) $edit['customer_id'] : '');
    $scope = ($v['scope'] ?? 'public') === 'personal' ? 'personal' : 'public';

    echo '<style>.mgd-nc-admin .card{margin-bottom:1rem}.mgd-nc-admin .badge{font-weight:600}.mgd-nc-admin textarea{min-height:8rem}</style>';
    echo '<div class="mgd-nc-admin">' . $msg;
    // Formular
    echo '<div class="card"><div class="card-header"><h3 class="mb-0">' . (!empty($v['id']) ? 'Meldung bearbeiten' : 'Neue Meldung') . '</h3></div><div class="card-body">'
        . '<form method="post">' . $hidden . '<input type="hidden" name="mgd_nc_action" value="save"><input type="hidden" name="id" value="' . (int) ($v['id'] ?? 0) . '">'
        . '<div class="form-group"><label for="mgd-nc-title">Titel</label><input class="form-control" id="mgd-nc-title" name="title" maxlength="255" required value="' . $e($v['title'] ?? '') . '"></div>'
        . '<div class="form-group"><label for="mgd-nc-body">Text</label><textarea class="form-control" id="mgd-nc-body" name="body">' . $e($v['body'] ?? '') . '</textarea><small class="form-text text-muted">Erlaubt: einfache Formatierung (&lt;strong&gt;, &lt;em&gt;, Listen) und Links (&lt;a href="https://…"&gt;). Alles andere wird entfernt.</small></div>'
        . '<div class="form-group"><label for="mgd-nc-url">Link (optional)</label><input class="form-control" id="mgd-nc-url" name="target_url" placeholder="https:// oder /seite" value="' . $e($v['target_url'] ?? '') . '"></div>'
        . '<div class="form-group"><span class="d-block mb-1">Bereich</span>'
        . '<div class="custom-control custom-radio custom-control-inline"><input type="radio" class="custom-control-input" id="mgd-nc-public" name="scope" value="public"' . ($scope === 'public' ? ' checked' : '') . '><label class="custom-control-label" for="mgd-nc-public">Öffentlich (alle Besucher)</label></div>'
        . '<div class="custom-control custom-radio custom-control-inline"><input type="radio" class="custom-control-input" id="mgd-nc-personal" name="scope" value="personal"' . ($scope === 'personal' ? ' checked' : '') . '><label class="custom-control-label" for="mgd-nc-personal">Persönlich (ein Kunde)</label></div></div>'
        . '<div class="form-group"><label for="mgd-nc-customer">Kunde (nur bei persönlich)</label><input class="form-control" id="mgd-nc-customer" name="customer" placeholder="Kundennummer oder E-Mail" value="' . $e($customer) . '"></div>'
        . '<div class="form-row"><div class="form-group col-md-4"><label for="mgd-nc-status">Status</label><select class="custom-select" id="mgd-nc-status" name="status">';
    foreach (NotificationAdmin::STATUSES as $key => $label) {
        echo '<option value="' . $key . '"' . (($v['status'] ?? 'published') === $key ? ' selected' : '') . '>' . $label . '</option>';
    }
    echo '</select></div>'
        . '<div class="form-group col-md-4"><label for="mgd-nc-pub">Veröffentlichen ab</label><input type="datetime-local" step="1" class="form-control" id="mgd-nc-pub" name="published_at" value="' . $e(isset($form['published_at']) ? $form['published_at'] : $local($edit['published_at'] ?? null)) . '"><small class="form-text text-muted">Leer = sofort</small></div>'
        . '<div class="form-group col-md-4"><label for="mgd-nc-exp">Ablaufen am (optional)</label><input type="datetime-local" step="1" class="form-control" id="mgd-nc-exp" name="expires_at" value="' . $e(isset($form['expires_at']) ? $form['expires_at'] : $local($edit['expires_at'] ?? null)) . '"></div></div>'
        . '<button type="submit" class="btn btn-primary">Meldung speichern</button>' . (!empty($v['id']) ? ' <a class="btn btn-outline-secondary" href="' . $e(strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '#')) . '">Neue Meldung</a>' : '')
        . '</form></div></div>';
    // Liste
    // Seiten, je 50
    $perPage = 50;
    $total = $admin->total();
    $pages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($pages, (int) ($_POST['mgd_nc_page'] ?? 1)));
    $rows = $admin->latest($perPage, $page);
    echo '<div class="card"><div class="card-header"><h3 class="mb-0">Vorhandene Meldungen (' . $total . ')</h3></div><div class="card-body p-0">';
    if (!$rows) {
        echo '<p class="p-3 mb-0 text-muted">Noch keine Meldungen. Oben die erste anlegen.</p>';
    } else {
        echo '<div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Titel</th><th>Bereich</th><th>Status</th><th>Ab</th><th>Bis</th><th class="text-right">Aktion</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $badge = ['published' => 'success', 'draft' => 'secondary', 'archived' => 'light'][$r['status']] ?? 'secondary';
            echo '<tr><td>' . $e($r['title']) . '</td><td>' . ($r['scope'] === 'personal' ? 'Persönlich' : 'Öffentlich') . '</td>'
                . '<td><span class="badge badge-' . $badge . '">' . $e(NotificationAdmin::STATUSES[$r['status']] ?? $r['status']) . '</span></td>'
                . '<td>' . $e($r['published_at'] ? date('d.m.Y H:i', (int) strtotime($r['published_at'] . ' UTC')) : '–') . '</td>'
                . '<td>' . $e($r['expires_at'] ? date('d.m.Y H:i', (int) strtotime($r['expires_at'] . ' UTC')) : '–') . '</td>'
                . '<td class="text-right text-nowrap"><form method="post" class="d-inline">' . $hidden . '<input type="hidden" name="mgd_nc_action" value="edit"><input type="hidden" name="id" value="' . (int) $r['id'] . '"><button class="btn btn-sm btn-outline-primary">Bearbeiten</button></form> '
                . ($r['status'] !== 'archived' ? '<form method="post" class="d-inline">' . $hidden . '<input type="hidden" name="mgd_nc_action" value="archive"><input type="hidden" name="id" value="' . (int) $r['id'] . '"><button class="btn btn-sm btn-outline-secondary">Archivieren</button></form>' : '')
                . '</td></tr>';
        }
        echo '</tbody></table></div>';
        if ($pages > 1) {
            echo '<div class="p-3 d-flex flex-wrap align-items-center" style="gap:.5rem"><span class="text-muted">Seite ' . $page . ' von ' . $pages . '</span>';
            foreach ([['Zurück', $page - 1], ['Weiter', $page + 1]] as [$label, $target]) {
                if ($target >= 1 && $target <= $pages) {
                    echo '<form method="post" class="d-inline">' . $hidden . '<input type="hidden" name="mgd_nc_action" value="page"><input type="hidden" name="mgd_nc_page" value="' . $target . '"><button class="btn btn-sm btn-outline-secondary">' . $label . '</button></form>';
                }
            }
            echo '</div>';
        }
    }
    echo '</div></div></div>';
} catch (\Throwable $ex) {
    echo '<div class="alert alert-warning" role="alert">Dieser Bereich konnte gerade nicht geladen werden. Der Shop läuft normal weiter.</div>';
    try {
        Shop::Container()->getLogService()->warning('MichaThemeV5NotificationCenter Meldungen: ' . get_class($ex) . ' in ' . basename($ex->getFile()) . ':' . $ex->getLine());
    } catch (\Throwable) {
        // egal
    }
}
