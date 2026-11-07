<?php

declare(strict_types=1);

namespace Plugin\MGDSucheJTLShop\Search;

/**
 * Liest ausschließlich veröffentlichte Merkmalwerte der aktuellen Shopsprache.
 *
 * Es werden weder Besucher-IDs, IP-Adressen noch Suchhistorien gespeichert. Die
 * vorbereitete Abfrage bindet den Suchbegriff als Parameter; die einzige direkt in
 * SQL eingesetzte Zahl ist das vorher hart begrenzte Ergebnislimit.
 */
final class CharacteristicSuggestionProvider
{
    public function __construct(
        private readonly object $database,
        private readonly SearchTermNormalizer $normalizer,
        private readonly SearchConfig $config,
        private readonly int $languageID
    ) {
    }

    /**
     * @return list<array{id: int, characteristic: string, value: string}>
     */
    public function findFor(string $searchTerm): array
    {
        $term = $this->normalizer->normalize($searchTerm);
        if (mb_strlen($term, 'UTF-8') < $this->config->minimumLength() || $this->languageID <= 0) {
            return [];
        }

        $condition = 'LOWER(TRIM(tmerkmalwertsprache.cWert)) = :term';
        $parameters = ['languageID' => $this->languageID, 'term' => $term];
        if ($this->config->usesPrefixMatching()) {
            // LIKE-Sonderzeichen müssen escaped werden, bevor der Präfixvergleich erfolgt.
            $parameters['term'] = addcslashes($term, '\\%_');
            $condition = 'LOWER(TRIM(tmerkmalwertsprache.cWert)) LIKE CONCAT(:term, \'%\')';
        }

        $rows = $this->database->getObjects(
            'SELECT DISTINCT
                    tmerkmalwertsprache.kMerkmalWert AS id,
                    COALESCE(tmerkmalsprache.cName, tmerkmal.cName) AS characteristicName,
                    tmerkmalwertsprache.cWert AS valueName
                FROM tmerkmalwertsprache
                INNER JOIN tmerkmalwert
                    ON tmerkmalwert.kMerkmalWert = tmerkmalwertsprache.kMerkmalWert
                INNER JOIN tmerkmal
                    ON tmerkmal.kMerkmal = tmerkmalwert.kMerkmal
                LEFT JOIN tmerkmalsprache
                    ON tmerkmalsprache.kMerkmal = tmerkmal.kMerkmal
                    AND tmerkmalsprache.kSprache = tmerkmalwertsprache.kSprache
                WHERE tmerkmalwertsprache.kSprache = :languageID
                    AND ' . $condition . '
                ORDER BY characteristicName ASC, valueName ASC
                LIMIT ' . $this->config->maximumHints(),
            $parameters
        );

        $suggestions = [];
        foreach ($rows as $row) {
            $id = isset($row->id) ? (int) $row->id : 0;
            $value = isset($row->valueName) ? trim((string) $row->valueName) : '';
            if ($id <= 0 || $value === '') {
                continue;
            }
            $suggestions[] = [
                'id' => $id,
                'characteristic' => trim((string) ($row->characteristicName ?? 'Merkmal')),
                'value' => $value,
            ];
        }

        return $suggestions;
    }
}
