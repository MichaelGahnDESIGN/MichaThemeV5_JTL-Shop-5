<?php

declare(strict_types=1);

namespace Plugin\MGDSucheJTLShop\Search;

/**
 * Bereitet einen Suchbegriff für einen gleichwertigen Vergleich mit Merkmalwerten vor.
 *
 * Die Normalisierung verändert nie die native JTL-Suche. Sie gilt ausschließlich für
 * die zusätzliche Hinweisabfrage und verhindert unnötige Datenbankabfragen für leere
 * oder rein aus Sonderzeichen bestehende Eingaben.
 */
final class SearchTermNormalizer
{
    public function normalize(string $term): string
    {
        $term = html_entity_decode($term, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $term = trim(preg_replace('/\s+/u', ' ', $term) ?? '');

        return mb_strtolower($term, 'UTF-8');
    }
}
