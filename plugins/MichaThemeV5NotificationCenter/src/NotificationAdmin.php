<?php
declare(strict_types=1);
namespace Plugin\MichaThemeV5NotificationCenter;

use JTL\DB\DbInterface;

/** Meldungen im Backend anlegen, bearbeiten, archivieren. Nur für den Admin-Reiter "Meldungen". */
final class NotificationAdmin
{
    public const STATUSES = ['draft' => 'Entwurf', 'published' => 'Veröffentlicht', 'archived' => 'Archiviert'];
    private const ALLOWED_TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'a'];

    public function __construct(private readonly DbInterface $db)
    {
    }

    /** Nur einfache Formatierung + Links (http/https oder /pfad), alles andere fliegt raus */
    public static function cleanBody(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        if (!class_exists(\DOMDocument::class)) {
            return htmlspecialchars(strip_tags($html), ENT_QUOTES);
        }
        $doc = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="mgd-root">' . $html . '</div>', \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $root = $doc->getElementById('mgd-root');
        if ($root === null) {
            return htmlspecialchars(strip_tags($html), ENT_QUOTES);
        }
        self::cleanNode($root);
        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    private static function cleanNode(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMElement) {
                $tag = strtolower($child->tagName);
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'template', 'noscript', 'svg', 'math', 'textarea', 'select'], true)) {
                    $node->removeChild($child);
                    continue;
                }
                self::cleanNode($child);
                if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                    // Tag weg, Inhalt bleibt
                    while ($child->firstChild !== null) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }
                $href = $tag === 'a' ? self::safeUrl($child->getAttribute('href')) : '';
                foreach (iterator_to_array($child->attributes) as $attr) {
                    $child->removeAttribute($attr->nodeName);
                }
                if ($tag === 'a') {
                    if ($href === '') {
                        while ($child->firstChild !== null) {
                            $node->insertBefore($child->firstChild, $child);
                        }
                        $node->removeChild($child);
                        continue;
                    }
                    $child->setAttribute('href', $href);
                }
            } elseif ($child instanceof \DOMComment) {
                $node->removeChild($child);
            }
        }
    }

    /** Link nur /pfad oder http(s), sonst leer */
    public static function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 2048 || preg_match('/[\x00-\x1F\x7F\\\\]/', $url)) {
            return '';
        }
        if ($url[0] === '/') {
            return str_starts_with($url, '//') ? '' : $url;
        }

        return preg_match('~^https?://[^\s]+$~i', $url) === 1 ? $url : '';
    }

    /** Datum aus dem Formular > UTC 'Y-m-d H:i:s' oder null */
    private static function date(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        // Eingabe in Shop-Zeitzone, gespeichert wird UTC
        $ts = strtotime($value);

        return $ts === false ? null : gmdate('Y-m-d H:i:s', $ts);
    }

    /** Kunde per Nummer oder E-Mail, 0 = nicht gefunden */
    public function findCustomer(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        if (ctype_digit($value)) {
            $row = $this->db->getObjects('SELECT kKunde FROM tkunde WHERE kKunde = :id LIMIT 1', ['id' => (int) $value]);
        } else {
            $row = $this->db->getObjects('SELECT kKunde FROM tkunde WHERE cMail = :mail LIMIT 1', ['mail' => $value]);
        }

        return (int) ($row[0]->kKunde ?? 0);
    }

    /**
     * Speichert eine Meldung. @return array{ok:bool, error?:string, id?:int}
     * @param array<string, mixed> $input
     */
    public function save(array $input): array
    {
        $title = trim(strip_tags((string) ($input['title'] ?? '')));
        if ($title === '' || mb_strlen($title) > 255) {
            return ['ok' => false, 'error' => 'Bitte einen Titel eingeben (höchstens 255 Zeichen).'];
        }
        $body = self::cleanBody((string) ($input['body'] ?? ''));
        $scope = ($input['scope'] ?? '') === 'personal' ? 'personal' : 'public';
        $status = array_key_exists((string) ($input['status'] ?? ''), self::STATUSES) ? (string) $input['status'] : 'draft';
        $target = self::safeUrl((string) ($input['target_url'] ?? ''));
        if (trim((string) ($input['target_url'] ?? '')) !== '' && $target === '') {
            return ['ok' => false, 'error' => 'Der Link muss mit https:// oder / beginnen.'];
        }
        $customerId = 0;
        if ($scope === 'personal') {
            $customerId = $this->findCustomer((string) ($input['customer'] ?? ''));
            if ($customerId <= 0) {
                return ['ok' => false, 'error' => 'Für eine persönliche Meldung braucht es einen vorhandenen Kunden (Kundennummer oder E-Mail).'];
            }
        }
        $published = self::date($input['published_at'] ?? null);
        $id = (int) ($input['id'] ?? 0);
        // gleiches Datum wie gespeichert (Formular kennt evtl. keine Sekunden) > Original behalten, sonst gilt sie im Browser als neue Meldung
        if ($id > 0 && $published !== null) {
            $old = $this->db->getObjects('SELECT published_at FROM xplugin_mgd_notifications WHERE id = :id', ['id' => $id]);
            $oldValue = (string) ($old[0]->published_at ?? '');
            if ($oldValue !== '' && substr($oldValue, 0, 16) === substr($published, 0, 16)) {
                $published = substr($oldValue, 0, 19);
            }
        }
        if ($status === 'published' && $published === null) {
            $published = gmdate('Y-m-d H:i:s');
        }
        $expires = self::date($input['expires_at'] ?? null);
        $data = ['scope' => $scope, 'status' => $status, 'title' => $title, 'body' => $body, 'target_url' => $target === '' ? null : $target, 'published_at' => $published, 'expires_at' => $expires];
        if ($id > 0) {
            $this->db->getAffectedRows('UPDATE xplugin_mgd_notifications SET scope = :scope, status = :status, title = :title, body = :body, target_url = :target_url, published_at = :published_at, expires_at = :expires_at WHERE id = :id', $data + ['id' => $id]);
        } else {
            $id = $this->db->insert('xplugin_mgd_notifications', (object) ($data + ['created_at' => gmdate('Y-m-d H:i:s')]));
        }
        // Empfänger: bei persönlich genau einer, sonst keiner. Gleicher Kunde > Zeile bleibt (gelesen/ausgeblendet bleibt erhalten)
        $current = $this->db->getObjects('SELECT customer_id FROM xplugin_mgd_notification_recipients WHERE notification_id = :id', ['id' => $id]);
        $same = $scope === 'personal' && count($current) === 1 && (int) $current[0]->customer_id === $customerId;
        if (!$same) {
            $this->db->getAffectedRows('DELETE FROM xplugin_mgd_notification_recipients WHERE notification_id = :id', ['id' => $id]);
            if ($scope === 'personal' && $id > 0) {
                $this->db->insert('xplugin_mgd_notification_recipients', (object) ['notification_id' => $id, 'customer_id' => $customerId]);
            }
        }

        return ['ok' => true, 'id' => $id];
    }

    public function archive(int $id): bool
    {
        return $id > 0 && $this->db->getAffectedRows("UPDATE xplugin_mgd_notifications SET status = 'archived' WHERE id = :id", ['id' => $id]) > 0;
    }

    public function find(int $id): ?array
    {
        $rows = $this->db->getObjects('SELECT n.id, n.scope, n.status, n.title, n.body, n.target_url, n.published_at, n.expires_at, r.customer_id FROM xplugin_mgd_notifications n LEFT JOIN xplugin_mgd_notification_recipients r ON r.notification_id = n.id WHERE n.id = :id LIMIT 1', ['id' => $id]);

        return isset($rows[0]) ? (array) $rows[0] : null;
    }

    /** @return list<array<string, mixed>> Seite für Seite, neueste zuerst */
    public function latest(int $limit = 50, int $page = 1): array
    {
        $limit = max(1, min(500, $limit));
        $offset = (max(1, $page) - 1) * $limit;

        return array_map(static fn (object $r): array => (array) $r, $this->db->getObjects('SELECT id, scope, status, title, published_at, expires_at FROM xplugin_mgd_notifications ORDER BY id DESC LIMIT ' . $limit . ' OFFSET ' . $offset));
    }

    public function total(): int
    {
        $rows = $this->db->getObjects('SELECT COUNT(*) AS c FROM xplugin_mgd_notifications');

        return (int) ($rows[0]->c ?? 0);
    }
}
