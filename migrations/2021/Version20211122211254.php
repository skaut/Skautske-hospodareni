<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211122211254 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ac_participants CHANGE event_type event_type VARCHAR(9) NOT NULL COMMENT \'(DC2Type:string_enum)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ac_participants CHANGE event_type event_type VARCHAR(7) NOT NULL COMMENT \'(DC2Type:string_enum)\'');
    }
}
