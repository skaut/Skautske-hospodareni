<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190505084443 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('INSERT INTO `ac_chitsCategory` (`id`, `label`, `short`, `type`, `virtual`, `orderby`, `deleted`) VALUES ' .
            '(20,	\'Vratka úč. poplatku\',	\'vr\',	\'out\',	1,	100,	0);');
        $this->addSql('INSERT INTO `ac_chitsCategory_object` (`categoryId`, `objectTypeId`) VALUES (20, \'general\'), (20, \'camp\');');
        $this->addSql(<<<EOT
UPDATE `ac_chits_item` SET
`price` = (-1 * `price`),
`priceText` = REPLACE(priceText, '-', ' '),
`category` = '20',
`category_operation_type` = 'out'
WHERE `category_operation_type` = 'in' and price < 0
EOT);
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DELETE FROM `ac_chitsCategory_object` WHERE `categoryId` = \'20\'');
        $this->addSql('DELETE FROM `ac_chitsCategory` WHERE `id` = \'20\'');
    }
}
