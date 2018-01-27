<?php

declare(strict_types = 1);

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180126153831 extends AbstractMigration
{

    private const OLD_CATEGORY_ID = 11; // "Hromadný příjmový doklad"
    private const NEW_CATEGORY_ID = 1; // "Příjmy od účastníků"

    public function up(Schema $schema)
    {
        $this->addSql('UPDATE ac_chits SET category = ? WHERE category = ?', [self::NEW_CATEGORY_ID, self::OLD_CATEGORY_ID]);

        $this->addSql('DELETE FROM ac_chitsCategory_object WHERE categoryId = ?', [self::OLD_CATEGORY_ID]);
        $this->addSql('DELETE FROM ac_chitsCategory WHERE id = ?', [self::OLD_CATEGORY_ID]);
    }

    public function down(Schema $schema)
    {
    }

}
