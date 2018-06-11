<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180611093151 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE pa_payment DROP FOREIGN KEY pa_payment_ibfk_5');
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE pa_payment ADD CONSTRAINT pa_payment_ibfk_5 FOREIGN KEY (groupId) REFERENCES pa_group (id) ON UPDATE CASCADE');
    }
}
