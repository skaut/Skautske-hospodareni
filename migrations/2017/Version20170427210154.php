<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170427210154 extends AbstractMigration
{

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE pa_group ADD email_template_subject VARCHAR(255) NOT NULL, CHANGE email_info email_template_body LONGTEXT NOT NULL, DROP email_demand");
    }


    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE pa_group DROP email_template_subject, CHANGE email_template_body email_info TEXT DEFAULT NULL COLLATE utf8_czech_ci, ADD email_demand TEXT DEFAULT NULL COLLATE utf8_czech_ci");
        $this->addSql("UPDATE pa_group SET email_template_subject = 'Informace o platbě'");
    }

}
