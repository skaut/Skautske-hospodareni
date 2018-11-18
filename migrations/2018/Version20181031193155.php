<?php declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181031193155 extends AbstractMigration
{
    public function up (Schema $schema) : void {
        $this->addSql ("INSERT INTO `ac_chitsCategory` (`id`, `label`, `short`, `type`, `virtual`, `orderby`, `deleted`) VALUES " .
            "('17', 'Příspěvky samosprávy', 'sa', 1, '0', '100', '0'), " .
            "('18', 'Ostatní příjmy', 'op', 1, '0', '100', '0'), " .
            "('19', 'Vybavení', 'v', 2, '0', '100', '0');"
        );
        $this->addSql ("INSERT INTO `ac_chitsCategory_object` (`categoryId`, `objectTypeId`) VALUES " .
            "('17', 'general')," .
            "('18', 'general')," .
            "('19', 'general');"
        );
    }

    public function down (Schema $schema) : void {
        $this->addSql ("DELETE FROM `ac_chitsCategory_object` WHERE `categoryId` IN (17, 18, 19);");
        $this->addSql ("DELETE FROM `ac_chitsCategory` WHERE `id` IN (17, 18, 19);");
    }
}
