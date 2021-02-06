<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210206080027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unsigned option for ac_chits related objects';
    }

    public function up(Schema $schema): void
    {
        $this->dropForeignKeys();

        $this->addSql('ALTER TABLE ac_chits CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE ac_chit_scan CHANGE chit_id chit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ac_chit_to_item CHANGE chit_id chit_id INT NOT NULL, CHANGE item_id item_id INT NOT NULL');
        $this->addSql(<<<SQL
            ALTER TABLE ac_chits_item
                CHANGE id id INT AUTO_INCREMENT NOT NULL,
                CHANGE price price DOUBLE PRECISION NOT NULL,
                CHANGE category category INT NOT NULL
        SQL);

        $this->addSql('ALTER TABLE ac_chitsCategory CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE ac_chitsCategory_object CHANGE category_id category_id INT NOT NULL');

        $this->addSql(<<<SQL
            ALTER TABLE ac_unit_budget_category 
                CHANGE id id INT AUTO_INCREMENT NOT NULL,
                CHANGE parentId parentId INT DEFAULT NULL,
                CHANGE value value DOUBLE PRECISION DEFAULT '0' NOT NULL,
                CHANGE year year SMALLINT NOT NULL
        SQL);
        $this->addForeignKeys();
    }

    public function down(Schema $schema): void
    {
        $this->dropForeignKeys();

        $this->addSql('ALTER TABLE ac_chits CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE ac_chit_scan CHANGE chit_id chit_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE ac_chit_to_item CHANGE chit_id chit_id INT UNSIGNED NOT NULL, CHANGE item_id item_id INT UNSIGNED NOT NULL');
        $this->addSql(<<<SQL
            ALTER TABLE ac_chits_item
                CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                CHANGE price price DOUBLE PRECISION UNSIGNED NOT NULL,
                CHANGE category category INT UNSIGNED NOT NULL
        SQL);

        $this->addSql('ALTER TABLE ac_chitsCategory CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE ac_chitsCategory_object CHANGE category_id category_id INT UNSIGNED NOT NULL');

        $this->addSql(<<<SQL
            ALTER TABLE ac_unit_budget_category 
                CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                CHANGE parentId parentId INT UNSIGNED DEFAULT NULL,
                CHANGE value value DOUBLE PRECISION UNSIGNED DEFAULT '0' NOT NULL,
                CHANGE year year SMALLINT UNSIGNED NOT NULL
        SQL);

        $this->addForeignKeys();
    }

    private function dropForeignKeys(): void
    {
        $this->addSql('ALTER TABLE ac_chit_scan DROP FOREIGN KEY FK_FEC2BFD22AEA3AE4');
        $this->addSql('ALTER TABLE ac_chit_to_item DROP FOREIGN KEY FK_2EA9AB792AEA3AE4');
        $this->addSql('ALTER TABLE ac_chit_to_item DROP FOREIGN KEY FK_2EA9AB79126F525E');

        $this->addSql('ALTER TABLE ac_chitsCategory_object DROP FOREIGN KEY FK_824C4F259C370B71');

        $this->addSql('ALTER TABLE ac_unit_budget_category DROP FOREIGN KEY FK_356BCD1F10EE4CEE');
    }

    private function addForeignKeys(): void
    {
        $this->addSql('ALTER TABLE ac_chit_scan ADD CONSTRAINT FK_FEC2BFD22AEA3AE4 FOREIGN KEY (chit_id) REFERENCES ac_chits (id)');
        $this->addSql('ALTER TABLE ac_chit_to_item ADD CONSTRAINT FK_2EA9AB792AEA3AE4 FOREIGN KEY (`chit_id`) REFERENCES `ac_chits` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT');
        $this->addSql('ALTER TABLE ac_chit_to_item ADD CONSTRAINT FK_2EA9AB79126F525E FOREIGN KEY (`item_id`) REFERENCES `ac_chits_item` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT');

        $this->addSql('ALTER TABLE ac_chitsCategory_object ADD CONSTRAINT FK_824C4F259C370B71 FOREIGN KEY (`category_id`) REFERENCES `ac_chitsCategory` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');

        $this->addSql('ALTER TABLE `ac_unit_budget_category` ADD CONSTRAINT FK_356BCD1F10EE4CEE FOREIGN KEY (`parentId`) REFERENCES `ac_unit_budget_category` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
    }
}
