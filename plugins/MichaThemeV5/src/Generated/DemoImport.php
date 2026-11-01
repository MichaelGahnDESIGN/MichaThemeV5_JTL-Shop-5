<?php

declare(strict_types=1);

namespace MichaThemeV5\Plugin;

defined('PFAD_ROOT') || exit;

/**
 * Demo-Inhalte für JTL-Shop 5: Import und Entfernen (Backend-Seite und CLI-Skript).
 *
 * Quelle sind demo-data/products.json und demo-data/images/ im Plugin. Es gibt keinen Netzwerkzugriff.
 * Alles, was der Import anlegt, steht mit seiner ID in der Plugin-Tabelle xplugin_michathemev5_demo.
 * Entfernen löscht ausschließlich diese Einträge (bei Artikeln zusätzlich nur mit Artikelnummer-Präfix MTDEMO-).
 * Die Logik (DemoImporter) arbeitet gegen DemoStore und ist ohne JTL testbar.
 */

/** Liest und prüft den Datensatz. */
final class DemoData
{
    /** @return array<string,mixed> */
    public static function load(string $dir): array
    {
        $file = rtrim($dir, '/') . '/products.json';
        if (!is_file($file)) {
            throw new \RuntimeException('Demo-Datensatz nicht gefunden.');
        }
        $d = json_decode((string) file_get_contents($file), true);
        if (!is_array($d)) {
            throw new \RuntimeException('Demo-Datensatz ist kein gültiges JSON.');
        }
        $errors = self::validate($d, $dir);
        if ($errors) {
            throw new \RuntimeException('Demo-Datensatz ungültig: ' . implode('; ', array_slice($errors, 0, 5)));
        }

        return $d;
    }

    /** @return list<string> Fehlertexte, leer = in Ordnung */
    public static function validate(array $d, string $dir): array
    {
        $e = [];
        $prefix = (string) ($d['sku_prefix'] ?? '');
        if ($prefix === '') {
            $e[] = 'sku_prefix fehlt';
        }
        $cats = [];
        foreach ((array) ($d['categories'] ?? []) as $c) {
            $slug = (string) ($c['slug'] ?? '');
            if (!preg_match('/^mtdemo-[a-z0-9-]+$/', $slug) || trim((string) ($c['name'] ?? '')) === '') {
                $e[] = 'Kategorie ungültig: ' . $slug;
            }
            $cats[$slug] = true;
        }
        $skus = [];
        foreach ((array) ($d['products'] ?? []) as $p) {
            $sku = (string) ($p['sku'] ?? '');
            if ($prefix === '' || !str_starts_with($sku, $prefix) || !preg_match('/^[A-Za-z0-9-]+$/', $sku)) {
                $e[] = "SKU ungültig: $sku";
            }
            $all = [$sku];
            foreach ((array) ($p['variations']['options'] ?? []) as $o) {
                $all[] = (string) ($o['sku'] ?? '');
            }
            foreach ($all as $s) {
                if (isset($skus[$s]) || !str_starts_with($s, $prefix)) {
                    $e[] = "SKU doppelt oder ohne Präfix: $s";
                }
                $skus[$s] = true;
            }
            foreach (['title', 'short', 'description', 'delivery_time'] as $k) {
                if (trim((string) ($p[$k] ?? '')) === '') {
                    $e[] = "$sku: $k fehlt";
                }
            }
            if (!is_numeric($p['price'] ?? null) || (float) $p['price'] <= 0) {
                $e[] = "$sku: Preis ungültig";
            }
            if (isset($p['compare_price']) && (!is_numeric($p['compare_price']) || (float) $p['compare_price'] <= (float) ($p['price'] ?? 0))) {
                $e[] = "$sku: Streichpreis ungültig";
            }
            if (!is_int($p['stock'] ?? null) || $p['stock'] < 0) {
                $e[] = "$sku: Bestand ungültig";
            }
            if (!isset($cats[(string) ($p['category'] ?? '')])) {
                $e[] = "$sku: Kategorie unbekannt";
            }
            if (count((array) ($p['images'] ?? [])) !== 3) {
                $e[] = "$sku: genau 3 Bilder erwartet";
            }
            foreach ((array) ($p['images'] ?? []) as $img) {
                if (!preg_match('~^images/[a-z0-9._-]+\.jpg$~', (string) $img) || !is_file(rtrim($dir, '/') . '/' . $img)) {
                    $e[] = "$sku: Bilddatei fehlt oder ungültig: $img";
                }
            }
        }

        return $e;
    }
}

/** Zugriff auf den Shop; im Test durch eine Attrappe ersetzt. */
interface DemoStore
{
    /** ID der vom Import angelegten Kategorie (nur markierte), sonst null. */
    public function findCategory(string $slug): ?int;

    /** @param array<string,mixed> $c */
    public function createCategory(array $c): int;

    /** true = markierter Demo-Artikel, false = fremder Artikel mit gleicher Artikelnummer, null = nicht vorhanden. */
    public function articleState(string $sku): ?bool;

    /**
     * @param array<string,mixed> $p
     * @param list<string> $imageFiles absolute Pfade der Bilder
     * @return int Anzahl neu angelegter Bilder
     */
    public function createArticle(array $p, int $categoryId, array $imageFiles): int;

    /** @return array{articles:int,categories:int,images:int,features:int} */
    public function counts(): array;

    /** @return array{articles:int,categories:int,images:int,features:int,categories_kept:int,skipped:int} */
    public function removeAll(): array;

    /** Wird nach Import und Entfernen aufgerufen (Shop-Cache leeren). */
    public function finish(): void;
}

final class DemoImporter
{
    public function __construct(private DemoStore $store, private string $dir)
    {
    }

    /** @return array{products:int,existing:int,foreign:int,categories:int,images:int} */
    public function import(): array
    {
        $d = DemoData::load($this->dir);
        $r = ['products' => 0, 'existing' => 0, 'foreign' => 0, 'categories' => 0, 'images' => 0];
        $cats = [];
        try {
            foreach ($d['categories'] as $c) {
                $id = $this->store->findCategory($c['slug']);
                if ($id === null) {
                    $id = $this->store->createCategory($c);
                    $r['categories']++;
                }
                $cats[$c['slug']] = $id;
            }
            foreach ($d['products'] as $p) {
                $state = $this->store->articleState($p['sku']);
                if ($state !== null) {
                    // Vorhandene Demo-Artikel bleiben, fremde Artikel mit gleicher Nummer werden nie angefasst.
                    $state ? $r['existing']++ : $r['foreign']++;
                    continue;
                }
                $files = array_map(fn (string $rel): string => rtrim($this->dir, '/') . '/' . $rel, $p['images']);
                $r['images'] += $this->store->createArticle($p, $cats[$p['category']], $files);
                $r['products']++;
            }
        } finally {
            $this->store->finish();
        }

        return $r;
    }

    /** @return array{articles:int,categories:int,images:int,features:int} */
    public function counts(): array
    {
        return $this->store->counts();
    }

    /** @return array{articles:int,categories:int,images:int,features:int,categories_kept:int,skipped:int} */
    public function remove(): array
    {
        try {
            return $this->store->removeAll();
        } finally {
            $this->store->finish();
        }
    }

    public static function total(array $counts): int
    {
        return (int) $counts['articles'] + (int) $counts['categories'] + (int) $counts['images'] + (int) $counts['features'];
    }
}

/** Kommandozeile: import | status | remove [--yes]. Ausgabe über $out, Rückfrage über $ask. */
final class DemoCli
{
    /** @param list<string> $args ohne Skriptname @param callable(string):void $out @param callable(string):bool $ask */
    public static function run(array $args, DemoImporter $imp, callable $out, callable $ask): int
    {
        $cmd = $args[0] ?? '';
        try {
            if ($cmd === 'import') {
                $r = $imp->import();
                $out(sprintf('%d Artikel neu, %d vorhanden, %d fremde mit gleicher Artikelnummer übersprungen; %d Kategorien und %d Bilder neu.', $r['products'], $r['existing'], $r['foreign'], $r['categories'], $r['images']));

                return 0;
            }
            if ($cmd === 'status') {
                $c = $imp->counts();
                $out(sprintf('Demo-Artikel: %d, Demo-Bilder: %d, Demo-Kategorien: %d, Demo-Merkmale: %d', $c['articles'], $c['images'], $c['categories'], $c['features']));

                return 0;
            }
            if ($cmd === 'remove') {
                $c = $imp->counts();
                if (DemoImporter::total($c) === 0) {
                    $out('Keine Demo-Inhalte vorhanden.');

                    return 0;
                }
                $out(sprintf('Zu löschen: %d Artikel, %d Bilder, %d Kategorien, %d Merkmale.', $c['articles'], $c['images'], $c['categories'], $c['features']));
                if (!in_array('--yes', $args, true) && !$ask('Diese Demo-Inhalte endgültig löschen? (ja/nein) ')) {
                    $out('Abgebrochen, nichts gelöscht.');

                    return 1;
                }
                $r = $imp->remove();
                $out(sprintf('Entfernt: %d Artikel, %d Bilder, %d Kategorien, %d Merkmale (%d Kategorien blieben, weil eigene Inhalte daran hängen; %d Einträge übersprungen).', $r['articles'], $r['images'], $r['categories'], $r['features'], $r['categories_kept'], $r['skipped']));

                return 0;
            }
        } catch (\Throwable $e) {
            $out('Fehler: ' . $e->getMessage());

            return 2;
        }
        $out('Aufruf: php demo-cli.php <import|status|remove [--yes]>');

        return 64;
    }
}
