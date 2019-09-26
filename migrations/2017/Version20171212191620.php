<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20171212191620 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("INSERT INTO ac_chitsCategory_object (categoryId, objectTypeId) VALUES (14, 'unit')");
    }

    public function down(Schema $schema) : void
    {
        $this->addSql("DELETE FROM ac_chitsCategory_object WHERE categoryId = 14 AND objectTypeId = 'unit'");
    }
}
