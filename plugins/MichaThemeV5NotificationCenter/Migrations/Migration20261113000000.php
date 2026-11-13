<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5NotificationCenter\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

// Reparatur 0.7.0: im Admin ohne Datum gespeicherte Meldungen haben 0000-00-00 statt NULL (Ablauf leer > Meldung unsichtbar)
class Migration20261113000000 extends Migration implements IMigration
{
    public function up(): void
    {
        $this->execute("UPDATE xplugin_mgd_notifications SET expires_at = NULL WHERE expires_at IS NOT NULL AND expires_at < '1000-01-01'");
        $this->execute("UPDATE xplugin_mgd_notifications SET published_at = NULL WHERE published_at IS NOT NULL AND published_at < '1000-01-01'");
    }

    public function down(): void
    {
        // nix zurück, NULL ist richtig
    }
}
