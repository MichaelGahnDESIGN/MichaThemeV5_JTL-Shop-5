<?php
declare(strict_types=1);
namespace Plugin\MichaThemeV5NotificationCenter;

use JTL\DB\DbInterface;

/** Liefert nur veröffentlichte Daten für die aktuelle Kundennummer an das Theme. */
final class NotificationBridge
{
    // max Einträge je Liste, gilt auch für "Alle anzeigen"
    public const LIMIT = 30;

    // Icons lokal, gleiche Auswahl wie bei WordPress
    public const ICONS = [
        'bell'      => ['Glocke', '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>'],
        'bell-ring' => ['Glocke mit Klang', '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/><path d="M4 2C2.8 3.7 2 5.7 2 8"/><path d="M22 8c0-2.3-.8-4.3-2-6"/>'],
        'bell-dot'  => ['Glocke mit Punkt', '<path d="M19.4 14.9C20.2 16.4 21 17 21 17H3s3-2 3-9c0-3.3 2.7-6 6-6 .7 0 1.3.1 1.9.3"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/><circle cx="18" cy="8" r="3"/>'],
        'megaphone' => ['Megafon', '<path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>'],
        'inbox'     => ['Posteingang', '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>'],
        'mail'      => ['Brief', '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>'],
        'message'   => ['Sprechblase', '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'],
        'info'      => ['Info', '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>'],
        'star'      => ['Stern', '<path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>'],
        'sparkles'  => ['Neuigkeiten', '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15z"/>'],
    ];

    /** @var array{visible_limit:int, icon:string} kommt aus den Plugin-Einstellungen (Bootstrap) */
    private static array $config = ['visible_limit' => 5, 'icon' => 'bell'];

    public static function configure(int $visibleLimit, string $icon): void
    {
        self::$config = ['visible_limit' => max(1, min(self::LIMIT, $visibleLimit)), 'icon' => isset(self::ICONS[$icon]) ? $icon : 'bell'];
    }

    public static function settings(): array
    {
        return self::$config;
    }

    public static function iconSvg(?string $key = null): string
    {
        $key = $key !== null && isset(self::ICONS[$key]) ? $key : self::$config['icon'];

        return '<svg class="mt-hdr-ico" aria-hidden="true" focusable="false" viewBox="0 0 24 24">' . self::ICONS[$key][1] . '</svg>';
    }

    public static function forTemplate(DbInterface $db, int $customerId): array
    {
        $now = gmdate('Y-m-d H:i:s');
        $public = $db->getObjects('SELECT id, title, body, target_url, published_at FROM xplugin_mgd_notifications WHERE scope = :scope AND status = :status AND published_at <= :now AND (expires_at IS NULL OR expires_at > :now) ORDER BY published_at DESC LIMIT ' . self::LIMIT, ['scope' => 'public', 'status' => 'published', 'now' => $now]);
        $personal = [];
        if ($customerId > 0) {
            // Ablaufdatum jetzt auch bei persönlichen Meldungen
            $personal = $db->getObjects('SELECT n.id, n.title, n.body, n.target_url, n.published_at, r.read_at FROM xplugin_mgd_notifications n INNER JOIN xplugin_mgd_notification_recipients r ON r.notification_id = n.id WHERE r.customer_id = :customer AND n.scope = :scope AND n.status = :status AND n.published_at <= :now AND (n.expires_at IS NULL OR n.expires_at > :now) AND r.dismissed_at IS NULL ORDER BY n.published_at DESC LIMIT ' . self::LIMIT, ['customer' => $customerId, 'scope' => 'personal', 'status' => 'published', 'now' => $now]);
        }

        return [
            'public'   => array_map(static fn (object $i): array => self::normalize($i, false), $public),
            'personal' => array_map(static fn (object $i): array => self::normalize($i, true), $personal),
            'settings' => self::$config,
        ];
    }

    private static function normalize(object $item, bool $personal): array
    {
        $row = ['id' => (int) ($item->id ?? 0), 'title' => (string) $item->title, 'body' => (string) $item->body, 'target_url' => (string) ($item->target_url ?? ''), 'published_at' => (string) ($item->published_at ?? ''), 'personal' => $personal];
        if ($personal) {
            $row['read'] = !empty($item->read_at);
        }

        return $row;
    }
}
