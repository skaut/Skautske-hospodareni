<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180701123223 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE ac_chits ADD payment_method VARCHAR(13) NOT NULL');
        $this->addSql('UPDATE ac_chits SET payment_method = \'cash\'');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE ac_chits DROP payment_method');
    }
}
