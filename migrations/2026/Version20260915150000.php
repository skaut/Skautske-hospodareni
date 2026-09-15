<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260915150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove obsolete Doctrine type comments no longer used by DBAL 4';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ac_chits_item CHANGE price price INT NOT NULL');
        $this->addSql('ALTER TABLE ac_unit_budget_category CHANGE value value INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE bank_transaction CHANGE amount amount INT NOT NULL');
        $this->addSql('ALTER TABLE pa_group CHANGE amount amount INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pa_payment CHANGE amount amount INT NOT NULL');
        $this->addSql('ALTER TABLE page_help CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE page_view_daily CHANGE day day DATE NOT NULL');
        $this->addSql('ALTER TABLE system_user_role CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user_login CHANGE logged_in_at logged_in_at DATETIME NOT NULL, CHANGE last_seen_at last_seen_at DATETIME NOT NULL, CHANGE logged_out_at logged_out_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ac_chits_item CHANGE price price INT NOT NULL COMMENT \'(DC2Type:money)\'');
        $this->addSql('ALTER TABLE ac_unit_budget_category CHANGE value value INT DEFAULT 0 NOT NULL COMMENT \'(DC2Type:money)\'');
        $this->addSql('ALTER TABLE bank_transaction CHANGE amount amount INT NOT NULL COMMENT \'(DC2Type:money)\'');
        $this->addSql('ALTER TABLE pa_group CHANGE amount amount INT DEFAULT NULL COMMENT \'(DC2Type:money)\'');
        $this->addSql('ALTER TABLE pa_payment CHANGE amount amount INT NOT NULL COMMENT \'(DC2Type:money)\'');
        $this->addSql('ALTER TABLE page_help CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE page_view_daily CHANGE day day DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE system_user_role CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_login CHANGE logged_in_at logged_in_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE last_seen_at last_seen_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE logged_out_at logged_out_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
