<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170528182347 extends AbstractMigration
{

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tc_commands ADD next_travel_id INT NOT NULL');
        $this->addSql('
            UPDATE tc_commands
            SET tc_commands.next_travel_id = (SELECT COALESCE (max(tc_travels.id) + 1, 0) FROM tc_travels WHERE command_id = tc_commands.id)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tc_commands DROP next_travel_id');
    }

}
