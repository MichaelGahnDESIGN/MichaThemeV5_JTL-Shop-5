<?php

declare(strict_types=1);

namespace Plugin\MGDSucheJTLShop\Search;

/**
 * Fügt lokale Assets und Merkmal-Hinweise in das fertige Smarty-Dokument ein.
 *
 * Die Links verwenden den nativen JTL-Parameter `m`. Dadurch laufen Auswahl,
 * Sichtbarkeit und Produktlisten weiterhin vollständig durch JTL-Shop 5.
 */
final class SearchPageEnhancer
{
    public function __construct(
        private readonly object $plugin,
        private readonly CharacteristicSuggestionProvider $suggestions,
        private readonly SearchConfig $config,
        private readonly ?\Closure $onError = null
    ) {
    }

    /**
     * @param array<string, mixed> $args
     */
    public function inject(array $args): void
    {
        // Sicherheitsnetz: Hook läuft auf jeder Seite, Fehler darf die Seite nie weiß machen
        try {
            $this->enhance($args);
        } catch (\Throwable $e) {
            $this->report($e);
        }
    }

    private function report(\Throwable $e): void
    {
        if ($this->onError === null) {
            return;
        }
        try {
            ($this->onError)($e);
        } catch (\Throwable) {
            // Melder kaputt, egal
        }
    }

    /**
     * @param array<string, mixed> $args
     */
    private function enhance(array $args): void
    {
        $document = $args['document'] ?? null;
        if (is_object($document) === false || method_exists($document, 'find') === false) {
            return;
        }

        $baseURL = rtrim((string) $this->plugin->getPaths()->getBaseURL(), '/');
        $this->injectAssets($document, $baseURL);

        $term = $this->getSearchTerm();
        if ($term === '') {
            return;
        }

        $matches = $this->suggestions->findFor($term);
        if ($matches === []) {
            return;
        }

        // NOVA besitzt ein main-Element; ohne Treffer passiert absichtlich nichts.
        // Das ist sicherer als Ergebnislisten aus fremden Child-Templates zu überschreiben.
        $document->find('main')->first()->prepend($this->renderHints($matches));
    }

    private function injectAssets(object $document, string $baseURL): void
    {
        $css = htmlspecialchars($baseURL . '/frontend/css/mgd-search.css', ENT_QUOTES, 'UTF-8');
        $js = htmlspecialchars($baseURL . '/frontend/js/mgd-search.js', ENT_QUOTES, 'UTF-8');
        $accent = htmlspecialchars($this->config->accentColor(), ENT_QUOTES, 'UTF-8');

        $document->find('head')->append(
            '<link rel="stylesheet" href="' . $css . '">' .
            '<script defer src="' . $js . '" ' .
            'data-mgd-search-accent="' . $accent . '"></script>'
        );
    }

    /**
     * @param list<array{id: int, characteristic: string, value: string}> $matches
     */
    private function renderHints(array $matches): string
    {
        $items = '';
        foreach ($matches as $match) {
            $label = htmlspecialchars($match['characteristic'] . ': ' . $match['value'], ENT_QUOTES, 'UTF-8');
            $href = htmlspecialchars($this->currentPath() . '?m=' . $match['id'], ENT_QUOTES, 'UTF-8');
            $items .= '<li><a class="mgd-search-feature-link" href="' . $href . '">' . $label . '</a></li>';
        }

        $accent = htmlspecialchars($this->config->accentColor(), ENT_QUOTES, 'UTF-8');

        return '<section class="mgd-search-feature-hints mgd-search-feature-hints--' . $this->config->display() . '" '
            . 'style="--mgd-search-accent: ' . $accent . ';" '
            . 'aria-labelledby="mgd-search-feature-heading">'
            . '<h2 id="mgd-search-feature-heading">Passende Merkmale</h2>'
            . '<p>Diese Merkmale stammen aus Ihrem Sortiment. Ein Klick öffnet die passende JTL-Filteransicht.</p>'
            . '<ul>' . $items . '</ul></section>';
    }

    private function getSearchTerm(): string
    {
        $value = $_GET['qs'] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }

    private function currentPath(): string
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/search/'), PHP_URL_PATH);
        if (is_string($path) === false || str_starts_with($path, '/') === false) {
            return '/search/';
        }

        return $path;
    }
}
