<?php

declare(strict_types=1);

namespace MichaThemeV5\Plugin;

defined('PFAD_ROOT') || exit;

use JTL\Helpers\Form;
use JTL\Shop;

require_once __DIR__ . '/DemoImport.php';

/**
 * Backend-Seite "Demo-Inhalte" (Import und Entfernen) für die Plugin-Oberfläche.
 * Die Plugin-Hülle (Menüeintrag in info.xml) ruft nur DemoPage::render() auf und gibt das HTML aus.
 * Sicherheit: nur Hauptadministratoren, jeder Schreibzugriff per POST mit JTL-Token, Löschen nur nach Bestätigung
 * und nur, wenn der angezeigte Bestand noch stimmt.
 */
final class DemoPage
{
    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function allowed(): bool
    {
        try {
            $acc = Shop::Container()->getAdminAccount();

            return (int) ($acc->account()->oGroup->kAdminlogingruppe ?? 0) === (int) \ADMINGROUP;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @param array<string,mixed> $post */
    public static function render(DemoImporter $imp, string $method = 'GET', array $post = []): string
    {
        if (!self::allowed()) {
            return '<div class="alert alert-danger">Keine Berechtigung. Die Demo-Inhalte darf nur ein Hauptadministrator verwalten.</div>';
        }
        $msg = '';
        if (strtoupper($method) === 'POST') {
            $msg = self::act($imp, $post);
        }
        $c = $imp->counts();
        $total = DemoImporter::total($c);
        $h = '<h2>Demo-Inhalte</h2>' . $msg;
        $h .= '<p>Legt Beispielartikel mit Kategorien, Preisen, Bestand, Lieferzeit, Merkmalen, Varianten und je 3 Platzhalterbildern an. '
            . 'Alle Texte und Bilder sind frei erfunden, es werden keine Daten aus dem Netz geladen. Ein zweiter Import erzeugt keine Dubletten. '
            . 'Hinweis: Wer den Shop mit JTL-Wawi abgleicht, sollte die Demo-Inhalte nur in Test- und Demo-Shops nutzen.</p>';
        $h .= '<h3>Importieren</h3><form method="post">' . Form::getTokenInput() . '<input type="hidden" name="mt_demo_action" value="import">'
            . '<button type="submit" class="btn btn-primary">Demo-Inhalte importieren</button></form>';
        $h .= '<h3>Entfernen</h3><p>' . self::esc(sprintf('Aktuell vorhanden: %d Demo-Artikel, %d Bilder, %d Kategorien, %d Merkmale.', $c['articles'], $c['images'], $c['categories'], $c['features'])) . '</p>';
        if ($total === 0) {
            return $h . '<p>Es gibt nichts zu entfernen.</p>';
        }

        return $h . '<p>Gelöscht wird nur, was der Import angelegt hat. Eigene Artikel, Bilder und Kategorien bleiben unverändert. Das kann nicht rückgängig gemacht werden.</p>'
            . '<form method="post">' . Form::getTokenInput() . '<input type="hidden" name="mt_demo_action" value="remove">'
            . '<input type="hidden" name="mt_demo_expect" value="' . $total . '">'
            . '<p><label><input type="checkbox" name="mt_demo_confirm" value="1" required> '
            . self::esc(sprintf('Ja, diese %d Demo-Artikel, %d Bilder, %d Kategorien und %d Merkmale endgültig löschen.', $c['articles'], $c['images'], $c['categories'], $c['features'])) . '</label></p>'
            . '<button type="submit" class="btn btn-danger">Demo-Inhalte entfernen</button></form>';
    }

    /** @param array<string,mixed> $post */
    private static function act(DemoImporter $imp, array $post): string
    {
        if (!Form::validateToken()) {
            return '<div class="alert alert-danger">Ungültiges Formular-Token. Nichts geändert.</div>';
        }
        $action = (string) ($post['mt_demo_action'] ?? '');
        try {
            if ($action === 'import') {
                $r = $imp->import();

                return '<div class="alert alert-success">' . self::esc(sprintf('Import fertig: %d Artikel neu, %d schon vorhanden, %d Kategorien und %d Bilder neu. Fremde Artikel mit gleicher Artikelnummer übersprungen: %d.',
                    $r['products'], $r['existing'], $r['categories'], $r['images'], $r['foreign'])) . '</div>';
            }
            if ($action === 'remove') {
                if ((string) ($post['mt_demo_confirm'] ?? '') !== '1') {
                    return '<div class="alert alert-warning">Nichts gelöscht: Die Bestätigung fehlte.</div>';
                }
                if (DemoImporter::total($imp->counts()) !== (int) ($post['mt_demo_expect'] ?? -1)) {
                    return '<div class="alert alert-warning">Nichts gelöscht: Der Bestand hat sich seit der Anzeige geändert. Bitte neu prüfen und erneut bestätigen.</div>';
                }
                $r = $imp->remove();

                return '<div class="alert alert-success">' . self::esc(sprintf('Entfernt: %d Artikel, %d Bilder, %d Kategorien, %d Merkmale. Kategorien, die noch eigene Inhalte enthalten, blieben: %d.',
                    $r['articles'], $r['images'], $r['categories'], $r['features'], $r['categories_kept'])) . '</div>';
            }
        } catch (\Throwable $e) {
            return '<div class="alert alert-danger">' . self::esc('Der Vorgang ist fehlgeschlagen: ' . $e->getMessage()) . '</div>';
        }

        return '';
    }
}
