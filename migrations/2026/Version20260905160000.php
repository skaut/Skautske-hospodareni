<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restore zero default on budget category amount lost during the money conversion';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ac_unit_budget_category MODIFY value INT DEFAULT 0 NOT NULL COMMENT \'(DC2Type:money)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ac_unit_budget_category MODIFY value INT NOT NULL COMMENT \'(DC2Type:money)\'');
    }
}
