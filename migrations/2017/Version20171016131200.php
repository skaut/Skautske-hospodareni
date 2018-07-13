<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20171016131200 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("UPDATE pa_smtp SET secure = 'tls' WHERE secure = 'tsl'");
    }

    public function down(Schema $schema) : void
    {
    }
}
