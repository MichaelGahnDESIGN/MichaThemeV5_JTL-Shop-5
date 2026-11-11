<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5NotificationCenter\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

// Tabellen für die Glocke, gleiche SQL wie database/schema.sql
class Migration20261111000000 extends Migration implements IMigration
{
    public function up(): void
    {
        $this->execute(
            'CREATE TABLE IF NOT EXISTS xplugin_mgd_notifications ('
            . 'id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, '
            . 'scope VARCHAR(16) NOT NULL, '
            . "status VARCHAR(16) NOT NULL DEFAULT 'draft', "
            . 'source_key VARCHAR(191) NULL UNIQUE, '
            . 'title VARCHAR(255) NOT NULL, '
            . 'body TEXT NOT NULL, '
            . 'target_url VARCHAR(2048) NULL, '
            . 'published_at DATETIME NULL, '
            . 'expires_at DATETIME NULL, '
            . 'created_at DATETIME NOT NULL, '
            . 'KEY feed (scope,status,published_at)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        $this->execute(
            'CREATE TABLE IF NOT EXISTS xplugin_mgd_notification_recipients ('
            . 'id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, '
            . 'notification_id INT UNSIGNED NOT NULL, '
            . 'customer_id INT UNSIGNED NOT NULL, '
            . 'read_at DATETIME NULL, '
            . 'dismissed_at DATETIME NULL, '
            . 'UNIQUE KEY recipient (notification_id,customer_id), '
            . 'KEY inbox (customer_id,read_at)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    public function down(): void
    {
        // Meldungen bleiben bei Deinstallation erhalten, nix löschen
    }
}
