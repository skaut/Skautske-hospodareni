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
        $this->addSql(<<<'SQL'
            INSERT INTO `ac_chitsCategory` (`id`, `name`, `shortcut`, `operation_type`, `virtual`, `priority`, `deleted`) VALUES
            (23, 'Vratka úč. poplatku - účastník',  'vru',  'out', 1, 100, 0),
            (24, 'Vratka úč. poplatku - instruktor','vri', 'out', 1, 100, 0);
        SQL);
        $this->addSql('INSERT INTO `ac_chitsCategory_object` (`category_id`, `type`) VALUES (23, \'education\'), (24, \'education\');');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM `ac_chitsCategory_object` WHERE `category_id` IN (\'23\', \'24\');');
        $this->addSql('DELETE FROM `ac_chitsCategory` WHERE `id` IN (\'23\', \'24\');');
    }
}
