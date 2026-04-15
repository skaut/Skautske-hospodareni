<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add automatic bank pairing settings to payment groups (pa_group)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pa_group ADD automatic_pairing_enabled TINYINT(1) DEFAULT 0 NOT NULL, ADD pairing_days_back INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pa_group DROP automatic_pairing_enabled, DROP pairing_days_back');
    }
}
