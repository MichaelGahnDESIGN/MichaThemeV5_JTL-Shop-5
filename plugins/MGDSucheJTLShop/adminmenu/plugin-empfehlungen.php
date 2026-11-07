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
    <section class="card">
        <div class="card-header"><h1 class="h3 mb-0">Weitere MGD-Plugins</h1></div>
        <div class="card-body">
            <p>Empfehlungen für JTL-Shop 5. Installierte Plugins werden direkt erkannt. Liste: <strong><?= $escape($catalog->source()) ?></strong>.</p>
            <p class="text-muted">Der Katalog wird nur im Backend von einer festen öffentlichen Adresse abgerufen. Es werden keine Shop-, Konto- oder Besucherdaten übertragen. Eine Installation erfolgt nie automatisch.</p>
            <div class="row">
                <?php foreach ($items as $item): ?>
                    <article class="col-12 col-lg-6 mb-3">
                        <div class="card h-100"><div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <h2 class="h5"><?= $escape($item['name']) ?></h2>
                                <span class="badge <?= $item['installed'] ? 'badge-success' : 'badge-secondary' ?>"><?= $item['installed'] ? 'Installiert' : 'Nicht installiert' ?></span>
                            </div>
                            <p><?= $escape($item['description']) ?></p>
                            <a class="btn btn-outline-primary" href="<?= $escape($item['url']) ?>" target="_blank" rel="noopener noreferrer">Details und Installation</a>
                        </div></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
} catch (Throwable $exception) {
    // Die Such- und Shopfunktion dürfen von einem Katalogfehler nie betroffen sein.
    echo '<div class="alert alert-warning" role="alert">Die Plugin-Empfehlungen sind gerade nicht erreichbar. Der Shop läuft normal weiter.</div>';
}
