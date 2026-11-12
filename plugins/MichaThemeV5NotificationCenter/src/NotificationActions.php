<?php
declare(strict_types=1);
namespace Plugin\MichaThemeV5NotificationCenter;

use JTL\DB\DbInterface;

/** Gelesen / Ausblenden für angemeldete Kunden, nur eigene und gerade sichtbare Einträge. */
final class NotificationActions
{
    public const ACTIONS = ['read', 'read_all', 'dismiss', 'dismiss_read'];

    /** @return int|null Anzahl geänderter Einträge, null bei Fehler */
    public static function handle(DbInterface $db, int $customerId, string $action, int $notificationId = 0): ?int
    {
        if ($customerId <= 0 || !in_array($action, self::ACTIONS, true)) {
            return null;
        }
        if (($action === 'read' || $action === 'dismiss') && $notificationId <= 0) {
            return null;
        }
        $now = gmdate('Y-m-d H:i:s');
        [$set, $where] = match ($action) {
            'read'         => ['read_at = COALESCE(read_at, :ts)', 'AND notification_id = :id'],
            'read_all'     => ['read_at = :ts', 'AND read_at IS NULL'],
            'dismiss'      => ['dismissed_at = :ts, read_at = COALESCE(read_at, :ts)', 'AND notification_id = :id'],
            'dismiss_read' => ['dismissed_at = :ts', 'AND read_at IS NOT NULL'],
        };
        $params = ['ts' => $now, 'now' => $now, 'customer' => $customerId];
        if (str_contains($where, ':id')) {
            $params['id'] = $notificationId;
        }
        // gleiche Sichtbarkeit wie die Bridge (keine geplanten/abgelaufenen)
        $sql = 'UPDATE xplugin_mgd_notification_recipients SET ' . $set . ' WHERE customer_id = :customer AND dismissed_at IS NULL ' . $where
            . ' AND notification_id IN (SELECT id FROM xplugin_mgd_notifications WHERE scope = \'personal\' AND status = \'published\' AND published_at <= :now AND (expires_at IS NULL OR expires_at > :now))';
        try {
            return $db->getAffectedRows($sql, $params);
        } catch (\Throwable) {
            return null;
        }
    }
}
