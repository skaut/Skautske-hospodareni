<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230928180113 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Přidání vratek pro vzdělávací akce';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO `ac_chitsCategory_object` (`category_id`, `type`) VALUES (20, \'education\');');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM `ac_chitsCategory_object` WHERE `category_id` = \'20\' AND `type` = \'education\';');
    }
}
