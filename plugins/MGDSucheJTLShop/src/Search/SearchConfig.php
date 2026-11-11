<?php

declare(strict_types=1);

namespace Plugin\MGDSucheJTLShop\Search;

/**
 * Kapselt Plugin-Einstellungen und begrenzt sie auf sichere Werte.
 *
 * Das Plugin akzeptiert keine frei einstellbaren SQL-Fragmente oder Selektoren.
 * So bleiben auch fehlerhafte Backend-Werte ohne Einfluss auf Datenbankabfragen
 * und die Ausgabe im Shop.
 */
final class SearchConfig
{
    public function __construct(private readonly object $plugin)
    {
    }

    public function isEnabled(): bool
    {
        return $this->value('mgd_search_enabled', 'Y') === 'Y';
    }

    public function usesPrefixMatching(): bool
    {
        return $this->value('mgd_search_match_mode', 'exact') === 'prefix';
    }

    public function minimumLength(): int
    {
        return $this->boundedNumber('mgd_search_minimum_length', 3, 2, 32);
    }

    public function maximumHints(): int
    {
        return $this->boundedNumber('mgd_search_maximum_hints', 6, 1, 20);
    }

    public function display(): string
    {
        return $this->value('mgd_search_display', 'cards') === 'compact' ? 'compact' : 'cards';
    }

    public function accentColor(): string
    {
        $color = $this->value('mgd_search_accent_color', '#5b4de3');

        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1 ? $color : '#5b4de3';
    }

    private function value(string $key, string $fallback): string
    {
        $value = $this->plugin->getConfig()->getValue($key);

        return is_scalar($value) ? (string) $value : $fallback;
    }

    private function boundedNumber(string $key, int $fallback, int $minimum, int $maximum): int
    {
        $value = filter_var($this->value($key, (string) $fallback), FILTER_VALIDATE_INT);
        if ($value === false) {
            return $fallback;
        }

        return max($minimum, min($maximum, $value));
    }
}
