<?php

declare(strict_types=1);

use JTL\Plugin\PluginInterface;
use JTL\Shop;

if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);
    echo 'Dieser Bereich ist nur in der JTL-Administration verfügbar.';

    return;
}

// Übersicht: Tabellen da? wieviele Meldungen live?
try {
    $db = Shop::Container()->getDB();
    $ready = $db->tableExists('xplugin_mgd_notifications') && $db->tableExists('xplugin_mgd_notification_recipients');
    if (!$ready) {
        echo '<div class="alert alert-warning" role="alert">Die Tabellen des Plugins fehlen. Bitte das Plugin einmal deaktivieren und wieder aktivieren oder die Datei database/schema.sql ausführen.</div>';

        return;
    }
    $now = gmdate('Y-m-d H:i:s');
    $row = $db->getSingleObject(
        "SELECT SUM(scope = 'public') AS pub, SUM(scope = 'personal') AS pers FROM xplugin_mgd_notifications"
        . " WHERE status = 'published' AND published_at <= :now AND (expires_at IS NULL OR expires_at > :now)",
        ['now' => $now]
    );
    $pub = (int) ($row->pub ?? 0);
    $pers = (int) ($row->pers ?? 0);
    echo '<div class="card"><div class="card-body">'
        . '<h3>Notification Center</h3>'
        . '<p>Die Tabellen sind angelegt.</p>'
        . '<ul><li>Öffentliche Meldungen sichtbar: ' . $pub . '</li><li>Persönliche Meldungen sichtbar: ' . $pers . '</li></ul>'
        . '<p>Die Glocke erscheint im Shop erst, wenn im Theme eine Platzierung gewählt ist und mindestens eine Meldung sichtbar ist.</p>'
        . '<p>Neue Meldungen schreiben Sie im Reiter „Meldungen“. Anzahl und Icon der Glocke stellen Sie unter „Einstellungen“ ein.</p>'
        . '</div></div>';
} catch (\Throwable $e) {
    echo '<div class="alert alert-warning" role="alert">Dieser Bereich konnte gerade nicht geladen werden. Der Shop läuft normal weiter.</div>';
}
