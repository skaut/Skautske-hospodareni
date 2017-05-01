<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170501172634 extends AbstractMigration
{

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE tc_commands ADD driver_name VARCHAR(255) NOT NULL, ADD driver_contact VARCHAR(255) NOT NULL, ADD driver_address VARCHAR(255) NOT NULL");
        $this->addSql("
            UPDATE tc_commands
            INNER JOIN tc_contracts as contract ON tc_commands.contract_id = contract.id
            SET
                tc_commands.driver_name = contract.driver_name,
                tc_commands.driver_contact = contract.driver_contact,
                tc_commands.driver_address = contract.driver_address;"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE tc_commands DROP driver_name, DROP driver_contact, DROP driver_address");
    }

}
