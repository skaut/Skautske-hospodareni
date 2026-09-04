<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add daily page view counters replacing the removed external analytics';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE page_view_daily (id INT UNSIGNED AUTO_INCREMENT NOT NULL, page_key VARCHAR(191) NOT NULL, day DATE NOT NULL COMMENT '(DC2Type:date_immutable)', views INT UNSIGNED NOT NULL, INDEX page_view_daily_day_idx (day), UNIQUE INDEX page_view_daily_page_day_uniq (page_key, day), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE page_view_daily');
    }
}
