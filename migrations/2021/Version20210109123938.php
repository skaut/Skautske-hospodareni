<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210109123938 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->connection->beginTransaction();
        $this->addSql('CREATE TABLE ac_camp_cashbooks  (id CHAR(36) NOT NULL COMMENT \'(DC2Type:skautis_camp_id)\', cashbook_id CHAR(36) NOT NULL COMMENT \'(DC2Type:cashbook_id)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ac_event_cashbooks (id CHAR(36) NOT NULL COMMENT \'(DC2Type:skautis_event_id)\', cashbook_id CHAR(36) NOT NULL COMMENT \'(DC2Type:cashbook_id)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');

        $this->addSql(<<<SQL
            INSERT INTO ac_camp_cashbooks (id, cashbook_id)
            SELECT skautisId as id, id as cashbook_id FROM ac_object WHERE type = 'camp'
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO ac_event_cashbooks (id, cashbook_id)
            SELECT skautisId as id, id as cashbook_id FROM ac_object WHERE type = 'general'
        SQL);

        $this->addSql('DROP TABLE ac_object');
        $this->connection->commit();
    }

    public function down(Schema $schema): void
    {
        $this->connection->beginTransaction();
        $this->addSql(<<<SQL
            CREATE TABLE `ac_object` (
              `id` varchar(36) COLLATE utf8_czech_ci NOT NULL,
              `skautisId` int unsigned NOT NULL,
              `type` varchar(20) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `skautisId_type` (`skautisId`,`type`),
              KEY `type` (`type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO ac_object (id, skautisId, `type`)
            SELECT cashbook_id, id, 'general' as type FROM `ac_event_cashbooks`
            
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO ac_object (id, skautisId, `type`)
            SELECT cashbook_id, id, 'camp' as type FROM `ac_camp_cashbooks`            
        SQL);

        $this->addSql('DROP TABLE ac_camp_cashbooks');
        $this->addSql('DROP TABLE ac_event_cashbooks');
        $this->connection->commit();
    }
}
