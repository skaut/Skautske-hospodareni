<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190521135746 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE ac_chits_item DROP id, CHANGE _id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql(<<<SQL
            ALTER TABLE ac_chits_item
                CHANGE COLUMN id _id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                ADD id INT(10) UNSIGNED NOT NULL
        SQL);
        $this->addSql('UPDATE ac_chits_item SET id = 1');
    }
}
