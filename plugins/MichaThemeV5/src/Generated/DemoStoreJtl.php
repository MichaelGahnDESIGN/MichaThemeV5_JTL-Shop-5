<?php

declare(strict_types=1);

namespace MichaThemeV5\Plugin;

defined('PFAD_ROOT') || exit;

/**
 * Umsetzung von DemoStore gegen die JTL-Shop-5-Datenbank.
 *
 * Schreibt direkt in die Katalogtabellen (wie die Wawi-Synchronisation). Die Spalten werden zur Laufzeit per SHOW COLUMNS
 * gelesen: Unbekannte Spalten werden weggelassen, Pflichtspalten ohne Vorgabe bekommen einen neutralen Wert.
 * Markierung: Plugin-Tabelle xplugin_michathemev5_demo (Art, ID, Referenz). Gelöscht wird nur, was dort steht.
 */
final class DemoStoreJtl implements DemoStore
{
    public const TABLE = 'xplugin_michathemev5_demo';
    public const SKU_PREFIX = 'MTDEMO-';
    public const IMAGE_DIR = 'mtdemo/';

    /** @var array<string,list<array{f:string,null:bool,def:mixed,auto:bool,type:string}>> */
    private array $cols = [];
    private bool $ready = false;
    /** @var array{lang:int,tax:int,rate:float,groups:list<int>}|null */
    private ?array $ctx = null;

    /** @param object $db JTL\DB\DbInterface (getObjects, getSingleObject, insert, delete, executeQuery) */
    public function __construct(private object $db, private string $root, private ?object $cache = null)
    {
        $this->root = rtrim($root, '/') . '/';
    }

    // ---- Tabellen und Markierung -------------------------------------------------------------------------------

    /** @return list<array{f:string,null:bool,def:mixed,auto:bool,type:string}> leer = Tabelle fehlt */
    private function cols(string $table): array
    {
        if (!isset($this->cols[$table])) {
            $rows = [];
            try {
                if (preg_match('/^[a-z0-9_]+$/', $table)) {
                    foreach ($this->db->getObjects('SHOW COLUMNS FROM ' . $table) as $r) {
                        $rows[] = ['f' => (string) $r->Field, 'null' => strtoupper((string) $r->Null) === 'YES', 'def' => $r->Default ?? null,
                            'auto' => str_contains(strtolower((string) ($r->Extra ?? '')), 'auto_increment'), 'type' => strtolower((string) $r->Type)];
                    }
                }
            } catch (\Throwable) {
                $rows = [];
            }
            $this->cols[$table] = $rows;
        }

        return $this->cols[$table];
    }

    private function ensureTable(): void
    {
        if ($this->ready) {
            return;
        }
        $this->db->executeQuery('CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' (cKind VARCHAR(24) NOT NULL, kId INT NOT NULL, cRef VARCHAR(190) NOT NULL, '
            . 'PRIMARY KEY (cKind, kId), KEY idx_ref (cKind, cRef)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4', 3);
        unset($this->cols[self::TABLE]);
        $this->ready = true;
    }

    private function track(string $kind, int $id, string $ref): void
    {
        $this->db->insert(self::TABLE, (object) ['cKind' => $kind, 'kId' => $id, 'cRef' => $ref]);
    }

    private function tracked(string $kind, string $ref): ?int
    {
        if ($this->cols(self::TABLE) === []) {
            return null;
        }
        $r = $this->db->getSingleObject('SELECT kId FROM ' . self::TABLE . ' WHERE cKind = :k AND cRef = :r', ['k' => $kind, 'r' => $ref]);

        return $r ? (int) $r->kId : null;
    }

    /** @return list<array{id:int,ref:string}> */
    private function trackedAll(string $kind): array
    {
        if ($this->cols(self::TABLE) === []) {
            return [];
        }
        $out = [];
        foreach ($this->db->getObjects('SELECT kId, cRef FROM ' . self::TABLE . ' WHERE cKind = :k ORDER BY kId', ['k' => $kind]) as $r) {
            $out[] = ['id' => (int) $r->kId, 'ref' => (string) $r->cRef];
        }

        return $out;
    }

    /** Referenz (cRef) einer Markierung nach Art und ID, null = nicht markiert. */
    private function refOf(string $kind, int $id): ?string
    {
        if ($this->cols(self::TABLE) === []) {
            return null;
        }
        $r = $this->db->getSingleObject('SELECT cRef FROM ' . self::TABLE . ' WHERE cKind = :k AND kId = :i', ['k' => $kind, 'i' => $id]);

        return $r ? (string) $r->cRef : null;
    }

    /** Stimmt der Inhalt der Zeile noch mit der Referenz überein, die der Import markiert hat? Sonst wird sie nie gelöscht. */
    private function rowMatches(string $table, string $keyCol, string $valueCol, int $id, string $expected): bool
    {
        if ($this->cols($table) === []) {
            return false;
        }
        foreach ($this->db->getObjects("SELECT $valueCol FROM $table WHERE $keyCol = :i", ['i' => $id]) as $row) {
            if ((string) $row->$valueCol === $expected) {
                return true;
            }
        }

        return false;
    }

    private function untrack(string $kind, int $id): void
    {
        $this->db->delete(self::TABLE, ['cKind', 'kId'], [$kind, $id]);
    }

    // ---- Zeilen schreiben --------------------------------------------------------------------------------------

    /**
     * Legt eine Zeile an. Nur vorhandene Spalten werden gesetzt. $idCol: Schlüsselspalte, die (falls kein AUTO_INCREMENT) per MAX+1 vergeben wird.
     *
     * @param array<string,mixed> $values
     */
    private function put(string $table, array $values, ?string $idCol = null): int
    {
        $cols = $this->cols($table);
        if ($cols === []) {
            throw new \RuntimeException("Tabelle $table nicht gefunden.");
        }
        $row = [];
        $id = 0;
        foreach ($cols as $c) {
            $f = $c['f'];
            if ($f === $idCol && !array_key_exists($f, $values)) {
                if ($c['auto']) {
                    continue;
                }
                $r = $this->db->getSingleObject("SELECT COALESCE(MAX($f), 0) + 1 AS n FROM $table");
                $id = (int) ($r->n ?? 1);
                $row[$f] = $id;
            } elseif (array_key_exists($f, $values)) {
                $row[$f] = $values[$f];
                if ($f === $idCol) {
                    $id = (int) $values[$f];
                }
            } elseif (!$c['null'] && $c['def'] === null && !$c['auto']) {
                $row[$f] = $this->neutral($c['type']);
            }
        }
        $got = (int) $this->db->insert($table, (object) $row);
        if ($idCol !== null && $id === 0) {
            $id = $got;
        }

        return $id;
    }

    private function neutral(string $type): mixed
    {
        if (preg_match('/int|decimal|float|double|numeric/', $type)) {
            return 0;
        }
        if (str_starts_with($type, 'datetime') || str_starts_with($type, 'timestamp')) {
            return '1970-01-02 00:00:00';
        }
        if (str_starts_with($type, 'date')) {
            return '1970-01-02';
        }

        return '';
    }

    /** @return array{lang:int,tax:int,rate:float,groups:list<int>} */
    private function context(): array
    {
        if ($this->ctx !== null) {
            return $this->ctx;
        }
        $l = $this->db->getSingleObject("SELECT kSprache FROM tsprache ORDER BY (cISO = 'ger') DESC, (cWawiStandard = 'Y') DESC, kSprache LIMIT 1");
        $t = $this->db->getSingleObject("SELECT k.kSteuerklasse AS kl, s.fSteuersatz AS satz FROM tsteuerklasse k LEFT JOIN tsteuersatz s ON s.kSteuerklasse = k.kSteuerklasse "
            . "ORDER BY (k.cStandard = 'Y') DESC, s.nPrio ASC, s.fSteuersatz DESC LIMIT 1");
        $g = [];
        foreach ($this->db->getObjects('SELECT kKundengruppe FROM tkundengruppe') as $r) {
            $g[] = (int) $r->kKundengruppe;
        }

        return $this->ctx = ['lang' => (int) ($l->kSprache ?? 1), 'tax' => (int) ($t->kl ?? 1), 'rate' => (float) ($t->satz ?? 19.0) ?: 19.0, 'groups' => $g ?: [1]];
    }

    private function net(string|float $gross): float
    {
        return round((float) $gross / (1 + $this->context()['rate'] / 100), 4);
    }

    private function seo(string $text, string $key, int $id): string
    {
        $s = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', strtr($text, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'ß' => 'ss'])));
        $s = trim($s, '-') ?: 'demo';
        $seo = $s;
        for ($i = 2; $this->db->getSingleObject('SELECT cSeo FROM tseo WHERE cSeo = :s', ['s' => $seo]); $i++) {
            $seo = $s . '-' . $i;
        }
        $this->put('tseo', ['cSeo' => $seo, 'cKey' => $key, 'kKey' => $id, 'kSprache' => $this->context()['lang']]);

        return $seo;
    }

    private function html(string $text): string
    {
        return '<p>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    // ---- DemoStore ---------------------------------------------------------------------------------------------

    public function findCategory(string $slug): ?int
    {
        return $this->tracked('kategorie', $slug);
    }

    public function createCategory(array $c): int
    {
        $this->ensureTable();
        $lang = $this->context()['lang'];
        $max = $this->db->getSingleObject('SELECT COALESCE(MAX(rght), 0) AS m FROM tkategorie');
        $lft = (int) ($max->m ?? 0) + 1;
        $now = date('Y-m-d H:i:s');
        $id = $this->put('tkategorie', ['kOberKategorie' => 0, 'cName' => $c['name'], 'cBeschreibung' => $this->html((string) ($c['description'] ?? '')), 'nSort' => 0,
            'dLetzteAktualisierung' => $now, 'lft' => $lft, 'rght' => $lft + 1, 'nLevel' => 1], 'kKategorie');
        $this->track('kategorie', $id, $c['slug']);
        $this->track('kategoriename', $id, (string) $c['name']); // Inhaltsprüfung beim Entfernen
        $seo = $this->seo((string) $c['name'], 'kKategorie', $id);
        $this->db->update('tkategorie', 'kKategorie', $id, (object) ['cSeo' => $seo]);
        $this->put('tkategoriesprache', ['kKategorie' => $id, 'kSprache' => $lang, 'cName' => $c['name'], 'cSeo' => $seo, 'cBeschreibung' => $this->html((string) ($c['description'] ?? ''))]);

        return $id;
    }

    public function articleState(string $sku): ?bool
    {
        $r = $this->db->getSingleObject('SELECT kArtikel FROM tartikel WHERE cArtNr = :n', ['n' => $sku]);
        if (!$r) {
            return null;
        }

        return $this->tracked('artikel', $sku) === (int) $r->kArtikel;
    }

    public function createArticle(array $p, int $categoryId, array $imageFiles): int
    {
        $this->ensureTable();
        $x = $this->context();
        $lang = $x['lang'];
        $now = date('Y-m-d H:i:s');
        $price = (float) $p['price'];
        $variations = !empty($p['variations']['options']);
        $days = 0;
        if (preg_match_all('/\d+/', (string) $p['delivery_time'], $m)) {
            $days = (int) max($m[0]);
        }
        $status = $this->deliveryStatus((string) $p['delivery_time']);
        $d = (array) ($p['dimensions'] ?? []);
        $id = $this->put('tartikel', [
            'kSteuerklasse' => $x['tax'], 'kLieferstatus' => $status, 'cName' => $p['title'], 'cArtNr' => $p['sku'],
            'cKurzBeschreibung' => $this->html((string) $p['short']), 'cBeschreibung' => $this->html((string) $p['description']),
            'fLagerbestand' => (float) $p['stock'], 'fLieferzeit' => (float) $days, 'fGewicht' => (float) ($p['weight'] ?? 0), 'fArtikelgewicht' => (float) ($p['weight'] ?? 0),
            'fLaenge' => (float) ($d['length'] ?? 0), 'fBreite' => (float) ($d['width'] ?? 0), 'fHoehe' => (float) ($d['height'] ?? 0),
            'fUVP' => isset($p['compare_price']) ? $this->net($p['compare_price']) : 0.0, 'fStandardpreisNetto' => $this->net($price),
            'cLagerBeachten' => 'Y', 'cLagerKleinerNull' => 'N', 'cLagerVariation' => $variations ? 'Y' : 'N', 'cTeilbar' => 'N', 'cNeu' => 'N', 'cTopArtikel' => 'N',
            'nSort' => 0, 'fMindestbestellmenge' => 1.0, 'fPackeinheit' => 1.0, 'nIstVater' => 0, 'kVaterArtikel' => 0, 'kEigenschaftKombi' => 0,
            'dErstellt' => $now, 'dLetzteAktualisierung' => $now,
        ], 'kArtikel');
        $this->track('artikel', $id, $p['sku']); // sofort markieren: bricht der Rest ab, räumt "Entfernen" auf
        $seo = $this->seo((string) $p['title'], 'kArtikel', $id);
        $this->db->update('tartikel', 'kArtikel', $id, (object) ['cSeo' => $seo]);
        $this->put('tartikelsprache', ['kArtikel' => $id, 'kSprache' => $lang, 'cName' => $p['title'], 'cSeo' => $seo,
            'cKurzBeschreibung' => $this->html((string) $p['short']), 'cBeschreibung' => $this->html((string) $p['description'])]);
        $this->put('tkategorieartikel', ['kArtikel' => $id, 'kKategorie' => $categoryId], 'kKategorieArtikel');

        foreach ($x['groups'] as $g) {
            $kp = $this->put('tpreis', ['kArtikel' => $id, 'kKundengruppe' => $g, 'kKunde' => 0], 'kPreis');
            $this->put('tpreisdetail', ['kPreis' => $kp, 'nAnzahlAb' => 0, 'fVKNetto' => $this->net($price)]);
        }
        foreach ((array) $p['attributes'] as $i => $a) {
            $this->feature($id, (string) $a['name'], (string) $a['value'], (int) $i);
        }
        if ($variations) {
            $this->variations($id, $p, $price);
        }

        return $this->images($id, $p, $imageFiles);
    }

    /** Lieferzeit-Text als Lieferstatus (vorhandenen gleichen Namens wiederverwenden, sonst markiert anlegen). */
    private function deliveryStatus(string $text): int
    {
        if ($this->cols('tlieferstatus') === []) {
            return 0;
        }
        $lang = $this->context()['lang'];
        $cols = array_column($this->cols('tlieferstatus'), 'f');
        $sql = in_array('kSprache', $cols, true) ? 'SELECT kLieferstatus FROM tlieferstatus WHERE cName = :n AND kSprache = :l' : 'SELECT kLieferstatus FROM tlieferstatus WHERE cName = :n';
        $r = $this->db->getSingleObject($sql, in_array('kSprache', $cols, true) ? ['n' => $text, 'l' => $lang] : ['n' => $text]);
        if ($r) {
            return (int) $r->kLieferstatus;
        }
        $next = $this->db->getSingleObject('SELECT COALESCE(MAX(kLieferstatus), 0) + 1 AS n FROM tlieferstatus');
        $id = (int) ($next->n ?? 1);
        $this->put('tlieferstatus', ['kLieferstatus' => $id, 'kSprache' => $lang, 'cName' => $text]);
        $this->track('lieferstatus', $id, $text);

        return $id;
    }

    private function feature(int $article, string $name, string $value, int $sort): void
    {
        $lang = $this->context()['lang'];
        $m = $this->tracked('merkmal', $name);
        if ($m === null) {
            $m = $this->put('tmerkmal', ['cName' => $name, 'nSort' => 0, 'nGlobal' => 1, 'cTyp' => 'TEXT'], 'kMerkmal');
            $this->track('merkmal', $m, $name);
            $this->put('tmerkmalsprache', ['kMerkmal' => $m, 'kSprache' => $lang, 'cName' => $name]);
        }
        $w = $this->tracked('merkmalwert', $name . '|' . $value);
        if ($w === null) {
            $w = $this->put('tmerkmalwert', ['kMerkmal' => $m, 'nSort' => $sort], 'kMerkmalWert');
            $this->track('merkmalwert', $w, $name . '|' . $value);
            $this->put('tmerkmalwertsprache', ['kMerkmalWert' => $w, 'kSprache' => $lang, 'cWert' => $value, 'cSeo' => strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $value))]);
        }
        $this->put('tartikelmerkmal', ['kArtikel' => $article, 'kMerkmal' => $m, 'kMerkmalWert' => $w], 'kArtikelMerkmal');
    }

    /** @param array<string,mixed> $p */
    private function variations(int $article, array $p, float $price): void
    {
        $lang = $this->context()['lang'];
        $e = $this->put('teigenschaft', ['kArtikel' => $article, 'cName' => $p['variations']['attribute'], 'cWaehlbar' => 'Y', 'cTyp' => 'SELECTBOX', 'nSort' => 1], 'kEigenschaft');
        $this->put('teigenschaftsprache', ['kEigenschaft' => $e, 'kSprache' => $lang, 'cName' => $p['variations']['attribute']]);
        foreach ($p['variations']['options'] as $i => $o) {
            $diff = isset($o['price']) ? $this->net((float) $o['price']) - $this->net($price) : 0.0;
            $w = $this->put('teigenschaftwert', ['kEigenschaft' => $e, 'cName' => $o['value'], 'cArtNr' => $o['sku'], 'fAufpreisNetto' => round($diff, 4), 'fGewichtDiff' => 0.0,
                'fLagerbestand' => (float) $o['stock'], 'fPackeinheit' => 1.0, 'nSort' => $i + 1], 'kEigenschaftWert');
            $this->put('teigenschaftwertsprache', ['kEigenschaftWert' => $w, 'kSprache' => $lang, 'cName' => $o['value']]);
        }
    }

    /** @param array<string,mixed> $p @param list<string> $files */
    private function images(int $article, array $p, array $files): int
    {
        $dir = $this->root . $this->storage() . self::IMAGE_DIR;
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Bildordner kann nicht angelegt werden.');
        }
        $n = 0;
        foreach ($files as $i => $file) {
            $ref = self::IMAGE_DIR . basename($file);
            $bild = $this->tracked('bild', $ref);
            if ($bild === null) {
                if (!@copy($file, $this->root . $this->storage() . $ref)) {
                    throw new \RuntimeException('Bilddatei konnte nicht kopiert werden.');
                }
                $bild = $this->put('tbild', ['cPfad' => $ref], 'kBild');
                $this->track('bild', $bild, $ref);
                $n++;
            }
            $this->put('tartikelpict', ['kArtikel' => $article, 'kBild' => $bild, 'cPfad' => $ref, 'nNr' => $i + 1, 'kMainArtikelBild' => 0], 'kArtikelPict');
        }

        return $n;
    }

    private function storage(): string
    {
        return defined('PFAD_MEDIA_IMAGE_STORAGE') ? rtrim((string) constant('PFAD_MEDIA_IMAGE_STORAGE'), '/') . '/' : 'media/image/storage/';
    }

    public function counts(): array
    {
        return ['articles' => count($this->trackedAll('artikel')), 'categories' => count($this->trackedAll('kategorie')), 'images' => count($this->trackedAll('bild')),
            'features' => count($this->trackedAll('merkmal')) + count($this->trackedAll('merkmalwert'))];
    }

    private function del(string $table, string $col, int $id): void
    {
        if ($this->cols($table) !== []) {
            $this->db->delete($table, $col, $id);
        }
    }

    /** @return list<int> */
    private function ids(string $sql, int $id, string $col): array
    {
        return array_map(static fn ($r): int => (int) $r->$col, $this->db->getObjects($sql, ['i' => $id]));
    }

    public function removeAll(): array
    {
        $r = ['articles' => 0, 'categories' => 0, 'images' => 0, 'features' => 0, 'categories_kept' => 0, 'skipped' => 0];
        foreach ($this->trackedAll('artikel') as $a) {
            $row = $this->db->getSingleObject('SELECT cArtNr FROM tartikel WHERE kArtikel = :i', ['i' => $a['id']]);
            if ($row && !str_starts_with((string) $row->cArtNr, self::SKU_PREFIX)) {
                $r['skipped']++; // Sicherung: fremder Artikel unter markierter ID, nie löschen
                $this->untrack('artikel', $a['id']);
                continue;
            }
            $id = $a['id'];
            foreach ($this->ids('SELECT kEigenschaft FROM teigenschaft WHERE kArtikel = :i', $id, 'kEigenschaft') as $e) {
                foreach ($this->ids('SELECT kEigenschaftWert FROM teigenschaftwert WHERE kEigenschaft = :i', $e, 'kEigenschaftWert') as $w) {
                    $this->del('teigenschaftwertsprache', 'kEigenschaftWert', $w);
                    $this->del('teigenschaftwert', 'kEigenschaftWert', $w);
                }
                $this->del('teigenschaftsprache', 'kEigenschaft', $e);
                $this->del('teigenschaft', 'kEigenschaft', $e);
            }
            foreach ($this->ids('SELECT kPreis FROM tpreis WHERE kArtikel = :i', $id, 'kPreis') as $kp) {
                $this->del('tpreisdetail', 'kPreis', $kp);
            }
            foreach (['tpreis', 'tartikelmerkmal', 'tkategorieartikel', 'tartikelsprache', 'tartikelpict'] as $t) {
                $this->del($t, 'kArtikel', $id);
            }
            $this->db->delete('tseo', ['cKey', 'kKey'], ['kArtikel', $id]);
            $this->del('tartikel', 'kArtikel', $id);
            $this->untrack('artikel', $id);
            $r['articles']++;
        }
        foreach ($this->trackedAll('bild') as $b) {
            if (!str_starts_with($b['ref'], self::IMAGE_DIR) || str_contains($b['ref'], '..')
                || !$this->rowMatches('tbild', 'kBild', 'cPfad', $b['id'], $b['ref'])) {
                $r['skipped']++; // Zeile gehört nicht (mehr) zum Import: nur die Markierung entfernen
                $this->untrack('bild', $b['id']);
                continue;
            }
            $this->del('tartikelpict', 'kBild', $b['id']);
            $this->del('tbild', 'kBild', $b['id']);
            if (str_starts_with($b['ref'], self::IMAGE_DIR) && !str_contains($b['ref'], '..')) {
                @unlink($this->root . $this->storage() . $b['ref']);
            }
            $this->rmTree($this->root . 'media/image/product/' . $b['id']);
            $this->untrack('bild', $b['id']);
            $r['images']++;
        }
        @rmdir($this->root . $this->storage() . self::IMAGE_DIR);
        foreach ($this->trackedAll('merkmalwert') as $w) {
            $value = substr($w['ref'], (int) strpos($w['ref'], '|') + 1);
            if (!str_contains($w['ref'], '|') || !$this->rowMatches('tmerkmalwertsprache', 'kMerkmalWert', 'cWert', $w['id'], $value)) {
                $r['skipped']++;
                $this->untrack('merkmalwert', $w['id']);
                continue;
            }
            if ($this->db->getSingleObject('SELECT kMerkmalWert FROM tartikelmerkmal WHERE kMerkmalWert = :i LIMIT 1', ['i' => $w['id']])) {
                continue; // hängt an einem fremden Artikel
            }
            $this->del('tmerkmalwertsprache', 'kMerkmalWert', $w['id']);
            $this->del('tmerkmalwert', 'kMerkmalWert', $w['id']);
            $this->untrack('merkmalwert', $w['id']);
            $r['features']++;
        }
        foreach ($this->trackedAll('merkmal') as $m) {
            if (!$this->rowMatches('tmerkmal', 'kMerkmal', 'cName', $m['id'], $m['ref'])) {
                $r['skipped']++;
                $this->untrack('merkmal', $m['id']);
                continue;
            }
            if ($this->db->getSingleObject('SELECT kMerkmalWert FROM tmerkmalwert WHERE kMerkmal = :i LIMIT 1', ['i' => $m['id']])) {
                continue;
            }
            $this->del('tmerkmalsprache', 'kMerkmal', $m['id']);
            $this->del('tmerkmal', 'kMerkmal', $m['id']);
            $this->untrack('merkmal', $m['id']);
            $r['features']++;
        }
        foreach ($this->trackedAll('lieferstatus') as $s) {
            if ($this->db->getSingleObject('SELECT kArtikel FROM tartikel WHERE kLieferstatus = :i LIMIT 1', ['i' => $s['id']])) {
                continue;
            }
            $this->del('tlieferstatus', 'kLieferstatus', $s['id']);
            $this->untrack('lieferstatus', $s['id']);
        }
        foreach ($this->trackedAll('kategorie') as $c) {
            $name = $this->refOf('kategoriename', $c['id']);
            if (!preg_match('/^mtdemo-[a-z0-9-]+$/', $c['ref']) || $name === null || !$this->rowMatches('tkategorie', 'kKategorie', 'cName', $c['id'], $name)) {
                $r['skipped']++; // Zeile gehört nicht (mehr) zum Import: nur die Markierung entfernen
                $this->untrack('kategorie', $c['id']);
                $this->untrack('kategoriename', $c['id']);
                continue;
            }
            $own =$this->db->getSingleObject('SELECT kKategorie FROM tkategorieartikel WHERE kKategorie = :i LIMIT 1', ['i' => $c['id']])
                ?: $this->db->getSingleObject('SELECT kKategorie FROM tkategorie WHERE kOberKategorie = :i LIMIT 1', ['i' => $c['id']]);
            if ($own) {
                $r['categories_kept']++; // eigene Artikel oder Unterkategorien hängen daran
                continue;
            }
            $this->del('tkategoriesprache', 'kKategorie', $c['id']);
            $this->db->delete('tseo', ['cKey', 'kKey'], ['kKategorie', $c['id']]);
            $this->del('tkategorie', 'kKategorie', $c['id']);
            $this->untrack('kategorie', $c['id']);
            $this->untrack('kategoriename', $c['id']);
            $r['categories']++;
        }

        return $r;
    }

    private function rmTree(string $dir): void
    {
        if (!is_dir($dir) || is_link($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            is_dir("$dir/$f") && !is_link("$dir/$f") ? $this->rmTree("$dir/$f") : @unlink("$dir/$f");
        }
        @rmdir($dir);
    }

    public function finish(): void
    {
        if ($this->cache !== null && method_exists($this->cache, 'flushTags')) {
            $tags = array_values(array_filter([defined('CACHING_GROUP_CATEGORY') ? constant('CACHING_GROUP_CATEGORY') : null, defined('CACHING_GROUP_ARTICLE') ? constant('CACHING_GROUP_ARTICLE') : null]));
            $this->cache->flushTags($tags ?: ['category', 'article']);
        }
    }
}
