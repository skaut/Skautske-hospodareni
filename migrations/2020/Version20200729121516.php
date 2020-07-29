<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200729121516 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE pa_group ADD oauth_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:ouath_id)\'');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE pa_group DROP oauth_id');
    }
}
