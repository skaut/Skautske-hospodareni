<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170304182827 extends AbstractMigration
{

    public function up(Schema $schema)
    {
        $dump = file_get_contents(__DIR__ . '/initial_schema.sql');

        $statement = '';
        foreach (explode(PHP_EOL, $dump) as $row) {
            if ($row === '') {
                $this->addSql(trim($statement));
                $statement = '';
            } else {
                $statement .= ' ' . trim($row);
            }
        }
    }

    public function down(Schema $schema)
    {
    }

}
