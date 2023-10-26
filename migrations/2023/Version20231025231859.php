<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231025231859 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Přidání roků pro pokladní knihy vzdělávacích akcí';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `ac_education_cashbooks` ADD COLUMN `year` INT NOT NULL AFTER `id`;');
        $this->addSql('ALTER TABLE `ac_education_cashbooks` DROP PRIMARY KEY, ADD PRIMARY KEY(`id`, `year`);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `ac_education_cashbooks` DROP PRIMARY KEY, ADD PRIMARY KEY(`id`);');
        $this->addSql('ALTER TABLE `ac_education_cashbooks` DROP COLUMN `year`;');
    }
}
