<?php

declare(strict_types=1);

use JTL\Plugin\PluginInterface;
use Plugin\MGDSucheJTLShop\Catalog\PluginCatalogProvider;

// Die Datei wird ausschließlich durch JTLs Plugin-Administration eingebunden.
// Ein direkter Aufruf darf weder Katalogdaten abrufen noch HTML ausgeben.
if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);
    echo 'Dieser Bereich ist nur in der JTL-Administration verfügbar.';

    return;
}

require_once dirname(__DIR__) . '/src/Catalog/PluginCatalogProvider.php';

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

try {
    $catalog = new PluginCatalogProvider();
    $items = $catalog->recommendations();
    ?>
    <style>
        /* Eigenständiges, gekapseltes Admin-Design: keine externen Schriften, Skripte oder Tracker. */
        .mgd-catalog{--mgd-ink:#17312f;--mgd-muted:#526460;--mgd-card:#fff;--mgd-line:#dbe3df;--mgd-accent:#17312f;max-width:1240px;margin:24px 0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:var(--mgd-ink)}.mgd-catalog *{box-sizing:border-box}.mgd-catalog__hero{padding:32px;overflow:hidden;border-radius:20px;background:radial-gradient(circle at 88% 28%,rgba(230,193,123,.28),transparent 26%),linear-gradient(130deg,#17312f,#0e2926);color:#fff}.mgd-catalog__eyebrow{margin:0 0 10px;font-size:12px;letter-spacing:.14em;font-weight:800;text-transform:uppercase;color:#e8cb91}.mgd-catalog__hero h1{max-width:620px;margin:0;font-size:clamp(28px,4vw,47px);line-height:1.05;color:#fff}.mgd-catalog__hero p{max-width:620px;margin:16px 0 0;font-size:16px;color:#e0e9e7}.mgd-catalog__summary{display:flex;flex-wrap:wrap;gap:18px;margin:22px 0;color:var(--mgd-muted);font-weight:600}.mgd-catalog__summary strong{color:var(--mgd-ink)}.mgd-catalog__notice{margin:0 0 18px;padding:13px 16px;border-left:4px solid #b17618;border-radius:9px;background:#fff8e8;color:#5d450d}.mgd-catalog__grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(285px,1fr));gap:22px}.mgd-catalog__card{display:flex;flex-direction:column;overflow:hidden;background:var(--mgd-card);border:1px solid var(--mgd-line);border-radius:16px;box-shadow:0 5px 16px rgba(18,49,46,.08)}.mgd-catalog__image{width:100%;height:164px;object-fit:cover;background:linear-gradient(135deg,var(--mgd-accent),#e8cb91)}.mgd-catalog__fallback-image{display:grid;place-items:center;width:100%;height:164px;background:linear-gradient(135deg,var(--mgd-accent),#315d58);font-size:56px;font-weight:800;color:#fff}.mgd-catalog__body{display:flex;flex:1;flex-direction:column;padding:22px}.mgd-catalog__top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}.mgd-catalog__tag,.mgd-catalog__state{display:inline-flex;align-items:center;padding:5px 9px;border-radius:999px;font-size:11px;line-height:1;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.mgd-catalog__tag{background:#eef3ef;color:#36504a}.mgd-catalog__state{background:#edf7ee;color:#27683a}.mgd-catalog__state--planned{background:#fff4dc;color:#805c0a}.mgd-catalog__title{margin:17px 0 4px;font-size:22px;line-height:1.15;color:var(--mgd-ink)}.mgd-catalog__meta{margin:0;color:var(--mgd-muted);font-size:14px}.mgd-catalog__description{margin:20px 0 0;line-height:1.58;color:#42534e}.mgd-catalog__features{display:grid;gap:7px;margin:18px 0 0;padding:0;list-style:none;color:#334641;font-size:14px}.mgd-catalog__features li::before{content:"✓";margin-right:8px;color:#257449;font-weight:900}.mgd-catalog__spacer{flex:1;min-height:16px}.mgd-catalog__footer{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:20px;padding-top:16px;border-top:1px solid var(--mgd-line)}.mgd-catalog__version{font-size:13px;color:var(--mgd-muted)}.mgd-catalog__actions{display:flex;flex-wrap:wrap;gap:9px}.mgd-catalog__button{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:8px 13px;border:1px solid var(--mgd-ink);border-radius:8px;background:var(--mgd-ink);color:#fff!important;font-size:14px;font-weight:750;text-decoration:none}.mgd-catalog__button:hover{filter:brightness(1.15);color:#fff}.mgd-catalog__link{color:var(--mgd-ink)!important;font-size:14px;font-weight:750;text-decoration:none}.mgd-catalog__link:hover{text-decoration:underline}.mgd-catalog__privacy{margin:25px 0 0;color:var(--mgd-muted);font-size:13px}@media (max-width:600px){.mgd-catalog__hero{padding:25px}.mgd-catalog__footer{align-items:flex-start;flex-direction:column}.mgd-catalog__actions{width:100%}.mgd-catalog__button{flex:1}}
    </style>
    <section class="mgd-catalog" aria-labelledby="mgd-catalog-title">
        <header class="mgd-catalog__hero">
            <p class="mgd-catalog__eyebrow">Michael Gahn DESIGN · Plugin-Kollektion</p>
            <h1 id="mgd-catalog-title">Mehr aus deinem Shop machen.</h1>
            <p>Entdecke passende Erweiterungen für JTL-Shop 5. Inhalte, Reihenfolge und Verfügbarkeit werden zentral über theme.michael-gahn.de gepflegt.</p>
        </header>
        <p class="mgd-catalog__summary"><strong><?= count($items) ?> Erweiterung<?= count($items) === 1 ? '' : 'en' ?></strong><span><?= count(array_filter($items, static fn (array $item): bool => $item['installed'])) ?> installiert</span><span>Keine automatische Installation</span></p>
        <?php if ($catalog->source() !== 'theme.michael-gahn.de'): ?>
            <p class="mgd-catalog__notice" role="status">Der zentrale Katalog ist gerade nicht erreichbar. Sie sehen die sichere lokale Ausfallsicherung. Ihr Shop läuft normal weiter.</p>
        <?php endif; ?>
        <div class="mgd-catalog__grid">
            <?php foreach ($items as $item): ?>
                <article class="mgd-catalog__card" style="--mgd-accent: <?= $escape($item['accent'] ?? '#17312f') ?>">
                    <?php if ($item['image_url'] !== null): ?>
                        <img class="mgd-catalog__image" src="<?= $escape($item['image_url']) ?>" alt="<?= $escape($item['image_alt']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="mgd-catalog__fallback-image" aria-hidden="true"><?= $escape(mb_strtoupper(mb_substr($item['name'], 0, 1))) ?></div>
                    <?php endif; ?>
                    <div class="mgd-catalog__body">
                        <div class="mgd-catalog__top"><span class="mgd-catalog__tag"><?= $escape($item['category']) ?></span><?php if ($item['installed']): ?><span class="mgd-catalog__state">Installiert</span><?php elseif ($item['status'] === 'announced'): ?><span class="mgd-catalog__state mgd-catalog__state--planned">Demnächst</span><?php endif; ?></div>
                        <h2 class="mgd-catalog__title"><?= $escape($item['name']) ?></h2>
                        <p class="mgd-catalog__meta"><?= $item['tier'] === 'paid' ? 'Kostenpflichtig' : 'Kostenlos' ?><?= $item['recommended'] ? ' · Empfohlen für MGD Suche' : '' ?></p>
                        <p class="mgd-catalog__description"><?= $escape($item['description']) ?></p>
                        <?php if ($item['features'] !== []): ?><ul class="mgd-catalog__features"><?php foreach ($item['features'] as $feature): ?><li><?= $escape($feature) ?></li><?php endforeach; ?></ul><?php endif; ?>
                        <div class="mgd-catalog__spacer"></div>
                        <footer class="mgd-catalog__footer"><span class="mgd-catalog__version"><?= $item['version'] !== '' ? 'Version ' . $escape($item['version']) : '' ?></span><span class="mgd-catalog__actions"><?php if (!$item['installed'] && $item['download_url'] !== null): ?><a class="mgd-catalog__button" href="<?= $escape($item['download_url']) ?>" target="_blank" rel="noopener noreferrer">Herunterladen</a><?php endif; ?><?php if ($item['details_url'] !== null): ?><a class="mgd-catalog__link" href="<?= $escape($item['details_url']) ?>" target="_blank" rel="noopener noreferrer"><?= $item['installed'] ? 'Verwalten ↗' : 'Details ↗' ?></a><?php endif; ?></span></footer>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <p class="mgd-catalog__privacy">Katalogquelle: <?= $escape($catalog->source()) ?>. Der Abruf erfolgt nur im JTL-Backend. Es werden keine Shop-, Konto- oder Besucherdaten übertragen; installiert wird ausschließlich über die JTL-Pluginverwaltung.</p>
    </section>
    <?php
} catch (Throwable $exception) {
    // Die Such- und Shopfunktion dürfen von einem Katalogfehler nie betroffen sein.
    echo '<div class="alert alert-warning" role="alert">Die Plugin-Empfehlungen sind gerade nicht erreichbar. Der Shop läuft normal weiter.</div>';
}
