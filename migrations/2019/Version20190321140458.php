<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190321140458 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(<<<SQL
            CREATE TABLE pa_group_unit (
                  id INT NOT NULL AUTO_INCREMENT,
                  unit_id INT NOT NULL,
                  group_id INT UNSIGNED NOT NULL,
                  INDEX IDX_FB5A0CD6FE54D947 (group_id),
                  PRIMARY KEY (id)
              )
              DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB
SQL
        );
        $this->addSql('ALTER TABLE pa_group_unit ADD CONSTRAINT FK_FB5A0CD6FE54D947 FOREIGN KEY (group_id) REFERENCES pa_group (id)');
        $this->addSql('INSERT INTO pa_group_unit (unit_id, group_id) SELECT unitId, id FROM pa_group');
        $this->addSql('ALTER TABLE pa_group DROP unitId');
    }

    public function down(Schema $schema) : void
    {
        // There is no going back! :D
    }
}
