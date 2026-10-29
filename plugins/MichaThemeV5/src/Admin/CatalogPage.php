<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Admin;

use MichaThemeV5\Plugin\PageRenderer;
use Plugin\MichaThemeV5\Catalog\CatalogService;
use Plugin\MichaThemeV5\Support\{FileKv, Perms};

defined('PFAD_ROOT') || exit;

/** Tab "Plugin-Empfehlungen": Liste von theme.michael-gahn.de, Fehler leise, Bilder lädt nur der Browser im Admin. */
final class CatalogPage extends Page
{
    public function tabKey(): string
    {
        return 'catalog';
    }

    public function render(): string
    {
        $svc = new CatalogService(new FileKv());
        $refresh = false;
        if ($this->postedAction() === 'catalog_refresh') {
            $err = $this->guard(true);
            if ($err !== null) {
                $this->back($err);
            } else {
                $refresh = true;
            }
        }
        $res = $svc->load($refresh);
        $html = PageRenderer::catalog($res, [
            'formOpen' => $this->formOpen('catalog_refresh'),
            'formClose' => '</form>',
            // Feed-Adressen sind schon geprüft (nur theme.michael-gahn.de); der Browser lädt das Symbol
            'imageUrl' => static fn (string $url): ?string => preg_match('~^https://theme\.michael-gahn\.de/uploads/catalog/[0-9a-f]{32}\.webp$~', $url) === 1 ? $url : null,
        ]);
        // Satz zu den Symbolen ganz ans Ende (vor das letzte schließende div)
        $end = strrpos($html, '</div>');
        if ($end !== false) {
            $html = substr_replace($html, '<p class="mtadm-meta">Die Symbole lädt Ihr Browser direkt von theme.michael-gahn.de (Ihre IP-Adresse geht dabei dorthin).</p>', $end, 0);
        }

        return $this->assets() . $this->notice() . $html;
    }
}
