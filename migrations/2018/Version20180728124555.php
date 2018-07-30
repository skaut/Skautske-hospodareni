<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180728124555 extends AbstractMigration
{
    private const VALID_CATEGORIES_FOR_CAMP = [
        7, // Převod do stř. pokladny
        8, // Neurčeno
        9, // Převod z pokladny střediska
        12, // Neurčeno
        13, // Převod z odd. pokladny
        14, // Převod do odd. pokladny
    ];

    public function up(Schema $schema) : void
    {
        $this->addSql(
            'DELETE FROM ac_chitsCategory_object WHERE objectTypeId = \'camp\' AND categoryId NOT IN (?)',
            [self::VALID_CATEGORIES_FOR_CAMP],
            [Connection::PARAM_INT_ARRAY]
        );
    }

    public function down(Schema $schema) : void
    {
    }
}
