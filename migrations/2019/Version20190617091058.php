<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190617091058 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE ac_chit_to_item DROP FOREIGN KEY FK_2EA9AB79126F525E');
        $this->addSql('ALTER TABLE ac_chit_to_item DROP FOREIGN KEY FK_2EA9AB792AEA3AE4');

        $this->addSql('DELETE i FROM `ac_chits_item` i LEFT JOIN ac_chit_to_item ci ON i.id = ci.item_id WHERE ci.chit_id IS NULL');

        $this->addSql('ALTER TABLE ac_chit_to_item CHANGE chit_id chit_id BIGINT UNSIGNED NOT NULL');

        $this->addSql('ALTER TABLE ac_chit_to_item ADD CONSTRAINT FK_2EA9AB79126F525E FOREIGN KEY (item_id) REFERENCES ac_chits_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ac_chit_to_item ADD CONSTRAINT FK_2EA9AB792AEA3AE4 FOREIGN KEY (chit_id) REFERENCES ac_chits (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE ac_chit_to_item DROP FOREIGN KEY FK_2EA9AB792AEA3AE4');
        $this->addSql('ALTER TABLE ac_chit_to_item DROP FOREIGN KEY FK_2EA9AB79126F525E');
        $this->addSql('ALTER TABLE ac_chit_to_item CHANGE chit_id chit_id BIGINT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE ac_chit_to_item ADD CONSTRAINT FK_2EA9AB792AEA3AE4 FOREIGN KEY (chit_id) REFERENCES ac_chits (id)');
        $this->addSql('ALTER TABLE ac_chit_to_item ADD CONSTRAINT FK_2EA9AB79126F525E FOREIGN KEY (item_id) REFERENCES ac_chits_item (id)');
    }
}
