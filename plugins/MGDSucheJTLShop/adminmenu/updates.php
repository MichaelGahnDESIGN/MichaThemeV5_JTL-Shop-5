<?php

declare(strict_types=1);

use JTL\Plugin\PluginInterface;
use Plugin\MGDSucheJTLShop\Update\ReleaseChecker;

if (!defined('PFAD_ROOT') || !isset($oPlugin) || !$oPlugin instanceof PluginInterface) {
    http_response_code(403);
    echo 'Dieser Bereich ist nur in der JTL-Administration verfügbar.';

    return;
}

require_once dirname(__DIR__) . '/src/Update/ReleaseChecker.php';

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$current = (string) $oPlugin->getMeta()->getVersion();
$enabled = $oPlugin->getConfig()->getValue('mgd_search_update_notices') === 'Y';
$result = (new ReleaseChecker())->check($current, $enabled);
?>
<section class="card">
    <div class="card-header"><h1 class="h3 mb-0">MGD Suche – Updates</h1></div>
    <div class="card-body">
        <?php if ($enabled === false): ?>
            <div class="alert alert-warning">Die GitHub-Updatehinweise sind deaktiviert. Du kannst sie unter „MGD Suche – Einstellungen“ aktivieren.</div>
        <?php elseif ($result['ok'] === false): ?>
            <div class="alert alert-warning">GitHub ist gerade nicht erreichbar oder es gibt noch kein passendes Release. Der Shop läuft unverändert weiter.</div>
        <?php elseif ($result['update']): ?>
            <div class="alert alert-success">
                <p><strong>Installiert:</strong> <?= $escape($result['current']) ?><br><strong>Verfügbar:</strong> <?= $escape((string) $result['latest']) ?></p>
                <a class="btn btn-primary" href="<?= $escape((string) $result['download_url']) ?>" target="_blank" rel="noopener noreferrer">Release-ZIP herunterladen</a>
                <a class="btn btn-outline-primary" href="<?= $escape((string) $result['release_url']) ?>" target="_blank" rel="noopener noreferrer">Release ansehen</a>
            </div>
        <?php else: ?>
            <div class="alert alert-success">Version <strong><?= $escape($result['current']) ?></strong> ist aktuell.</div>
        <?php endif; ?>
        <p class="text-muted mb-0">Sicherheitsprinzip: Das Plugin lädt oder installiert nie selbst Dateien. Das geprüfte ZIP wird bewusst über die JTL-Pluginverwaltung eingespielt.</p>
    </div>
</section>
