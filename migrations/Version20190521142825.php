<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190521142825 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE ac_chit_to_item (
                chit_id BIGINT(10) UNSIGNED NOT NULL,
                item_id INT UNSIGNED NOT NULL,
                INDEX IDX_2EA9AB792AEA3AE4 (chit_id),
                UNIQUE INDEX UNIQ_2EA9AB79126F525E (item_id),
                PRIMARY KEY(chit_id, item_id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB
         SQL);
        $this->addSql('ALTER TABLE ac_chit_to_item ADD CONSTRAINT FK_2EA9AB792AEA3AE4 FOREIGN KEY (chit_id) REFERENCES ac_chits (id)');
        $this->addSql('ALTER TABLE ac_chit_to_item ADD CONSTRAINT FK_2EA9AB79126F525E FOREIGN KEY (item_id) REFERENCES ac_chits_item (id)');

        $this->addSql('INSERT INTO ac_chit_to_item (chit_id, item_id) SELECT chit_id, id FROM ac_chits_item');

        $this->addSql('ALTER TABLE ac_chits_item DROP FOREIGN KEY ac_chits_item_ibfk_2');
        $this->addSql('DROP INDEX chit_id ON ac_chits_item');
        $this->addSql('ALTER TABLE ac_chits_item DROP chit_id');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE ac_chit_to_item');
        $this->addSql('ALTER TABLE ac_chits_item ADD chit_id BIGINT UNSIGNED NOT NULL');
        $this->addSql(<<<'SQL'
            UPDATE ac_chits_item i
                JOIN ac_chit_to_item jt ON jt.item_id = i.id
                SET i.chit_id = jt.chit_id
        SQL);
        $this->addSql('ALTER TABLE ac_chits_item ADD CONSTRAINT ac_chits_item_ibfk_2 FOREIGN KEY (chit_id) REFERENCES ac_chits (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('CREATE INDEX chit_id ON ac_chits_item (chit_id)');
    }
}
