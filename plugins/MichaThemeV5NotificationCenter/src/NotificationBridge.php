<?php
declare(strict_types=1);
namespace Plugin\MichaThemeV5NotificationCenter;

use JTL\DB\DbInterface;

/** Liefert nur veröffentlichte Daten für die aktuelle Kundennummer an das Theme. */
final class NotificationBridge
{
    public static function forTemplate(DbInterface $db, int $customerId): array
    {
        $now = gmdate('Y-m-d H:i:s');
        $public = $db->getObjects('SELECT title, body, target_url FROM xplugin_mgd_notifications WHERE scope = :scope AND status = :status AND published_at <= :now AND (expires_at IS NULL OR expires_at > :now)', ['scope' => 'public', 'status' => 'published', 'now' => $now]);
        $personal = [];
        if ($customerId > 0) {
            $personal = $db->getObjects('SELECT n.title, n.body, n.target_url FROM xplugin_mgd_notifications n INNER JOIN xplugin_mgd_notification_recipients r ON r.notification_id = n.id WHERE r.customer_id = :customer AND n.scope = :scope AND n.status = :status AND n.published_at <= :now AND r.dismissed_at IS NULL', ['customer' => $customerId, 'scope' => 'personal', 'status' => 'published', 'now' => $now]);
        }
        return ['public' => array_map([self::class, 'normalize'], $public), 'personal' => array_map([self::class, 'normalize'], $personal)];
    }

    private static function normalize(object $item): array
    {
        return ['title' => (string) $item->title, 'body' => (string) $item->body, 'target_url' => (string) ($item->target_url ?? '')];
    }
}
