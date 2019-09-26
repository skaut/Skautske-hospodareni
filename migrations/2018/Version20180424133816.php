<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180424133816 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(
            'ALTER TABLE pa_payment ADD transaction_payer VARCHAR(255) DEFAULT NULL, ADD transaction_note VARCHAR(255) DEFAULT NULL'
        );
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE pa_payment DROP transaction_payer, DROP transaction_note');
    }
}
