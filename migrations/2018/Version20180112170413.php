<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180112170413 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("UPDATE pa_payment SET vs = NULL WHERE vs = ''");
    }

    public function down(Schema $schema) : void
    {
    }
}
