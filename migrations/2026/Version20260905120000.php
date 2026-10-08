<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store all locally persisted monetary amounts as integer minor units';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE ac_chits_item SET price = ROUND(price * 100)');
        $this->addSql('UPDATE ac_unit_budget_category SET value = ROUND(value * 100)');
        $this->addSql('UPDATE pa_group SET amount = ROUND(amount * 100) WHERE amount IS NOT NULL');
        $this->addSql('UPDATE pa_payment SET amount = ROUND(amount * 100)');
        $this->addSql('UPDATE bank_transaction SET amount = ROUND(amount * 100)');
        $this->addSql('ALTER TABLE ac_chits_item MODIFY price INT NOT NULL COMMENT \'(DC2Type:money)\'');
        $this->addSql('ALTER TABLE ac_unit_budget_category MODIFY value INT NOT NULL COMMENT \'(DC2Type:money)\'');
        $this->addSql('ALTER TABLE pa_group MODIFY amount INT DEFAULT NULL COMMENT \'(DC2Type:money)\'');
        $this->addSql('ALTER TABLE pa_payment MODIFY amount INT NOT NULL COMMENT \'(DC2Type:money)\'');
        $this->addSql('ALTER TABLE bank_transaction MODIFY amount INT NOT NULL COMMENT \'(DC2Type:money)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ac_chits_item MODIFY price DOUBLE NOT NULL');
        $this->addSql('ALTER TABLE ac_unit_budget_category MODIFY value DOUBLE NOT NULL');
        $this->addSql('ALTER TABLE pa_group MODIFY amount DOUBLE DEFAULT NULL');
        $this->addSql('ALTER TABLE pa_payment MODIFY amount FLOAT NOT NULL');
        $this->addSql('ALTER TABLE bank_transaction MODIFY amount DOUBLE PRECISION NOT NULL');
        $this->addSql('UPDATE ac_chits_item SET price = price / 100');
        $this->addSql('UPDATE ac_unit_budget_category SET value = value / 100');
        $this->addSql('UPDATE pa_group SET amount = amount / 100 WHERE amount IS NOT NULL');
        $this->addSql('UPDATE pa_payment SET amount = amount / 100');
        $this->addSql('UPDATE bank_transaction SET amount = amount / 100');
    }
}
