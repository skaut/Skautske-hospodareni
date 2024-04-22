<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240422113851 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rozšíření pole spolucestujících u cesťáků na 256 znaků';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tc_commands MODIFY fellow_passengers VARCHAR(256) NOT NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tc_commands MODIFY fellow_passengers VARCHAR(64) NOT NULL;');
    }
}
