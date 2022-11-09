<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210206085905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unsigned option';
    }

    public function up(Schema $schema): void
    {
        $this->dropForeignKeys();
        $this->addSql('ALTER TABLE log CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql(<<<'SQL'
             ALTER TABLE pa_group 
                 CHANGE id id INT AUTO_INCREMENT NOT NULL,
                 CHANGE smtp_id smtp_id INT DEFAULT NULL
        SQL);
        $this->addSql('ALTER TABLE pa_group_email CHANGE group_id group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pa_group_unit CHANGE group_id group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pa_payment_email_recipients CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE pa_payment_sent_emails CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE tc_commands 
                CHANGE id id INT AUTO_INCREMENT NOT NULL,
                CHANGE vehicle_id vehicle_id INT DEFAULT NULL,
                CHANGE contract_id contract_id INT DEFAULT NULL,
                CHANGE unit_id unit_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tc_contracts 
                CHANGE id id INT AUTO_INCREMENT NOT NULL,
                CHANGE unit_id unit_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
             ALTER TABLE tc_travels 
                 CHANGE id id INT NOT NULL,
                 CHANGE command_id command_id INT NOT NULL,
                 CHANGE distance distance DOUBLE PRECISION DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tc_vehicle 
                CHANGE id id INT AUTO_INCREMENT NOT NULL,
                CHANGE unit_id unit_id INT NOT NULL,
                CHANGE consumption consumption DOUBLE PRECISION NOT NULL
        SQL);
        $this->addSql('ALTER TABLE tc_vehicle_roadworthy_scan CHANGE vehicle_id vehicle_id INT DEFAULT NULL');
        $this->addForeignKeys();
    }

    public function down(Schema $schema): void
    {
        $this->dropForeignKeys();
        $this->addSql('ALTER TABLE log CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE pa_group 
                CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, 
                CHANGE smtp_id smtp_id INT UNSIGNED DEFAULT NULL
        SQL);
        $this->addSql('ALTER TABLE pa_group_email CHANGE group_id group_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE pa_group_unit CHANGE group_id group_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE pa_payment_email_recipients CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE pa_payment_sent_emails CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->addSql(<<<'SQL'
             ALTER TABLE tc_commands 
                 CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, 
                 CHANGE vehicle_id vehicle_id INT UNSIGNED DEFAULT NULL, 
                 CHANGE unit_id unit_id INT UNSIGNED NOT NULL, 
                 CHANGE contract_id contract_id INT UNSIGNED DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tc_contracts 
                CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                CHANGE unit_id unit_id INT UNSIGNED NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tc_travels 
                CHANGE id id INT UNSIGNED NOT NULL,
                CHANGE command_id command_id INT UNSIGNED NOT NULL,
                CHANGE distance distance DOUBLE PRECISION UNSIGNED DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tc_vehicle 
                CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                CHANGE unit_id unit_id INT UNSIGNED NOT NULL,
                CHANGE consumption consumption DOUBLE PRECISION UNSIGNED NOT NULL
        SQL);
        $this->addSql('ALTER TABLE tc_vehicle_roadworthy_scan CHANGE vehicle_id vehicle_id INT UNSIGNED DEFAULT NULL');
        $this->addForeignKeys();
    }

    private function dropForeignKeys(): void
    {
        $this->addSql('ALTER TABLE pa_group_email DROP FOREIGN KEY FK_7A67EADBFE54D947');
        $this->addSql('ALTER TABLE pa_group_unit DROP FOREIGN KEY FK_FB5A0CD6FE54D947');
        $this->addSql('ALTER TABLE tc_commands DROP FOREIGN KEY FK_4D5B6D0C545317D1');
        $this->addSql('ALTER TABLE tc_travels DROP FOREIGN KEY FK_F53E53633E1689A');
        $this->addSql('ALTER TABLE tc_vehicle_roadworthy_scan DROP FOREIGN KEY FK_270D2917545317D1');
    }

    private function addForeignKeys(): void
    {
        $this->addSql('ALTER TABLE pa_group_email ADD CONSTRAINT FK_7A67EADBFE54D947 FOREIGN KEY (`group_id`) REFERENCES `pa_group` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
        $this->addSql('ALTER TABLE pa_group_unit ADD CONSTRAINT FK_FB5A0CD6FE54D947 FOREIGN KEY (`group_id`) REFERENCES `pa_group` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
        $this->addSql('ALTER TABLE tc_commands ADD CONSTRAINT FK_4D5B6D0C545317D1 FOREIGN KEY (`vehicle_id`) REFERENCES `tc_vehicle` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
        $this->addSql('ALTER TABLE tc_travels ADD CONSTRAINT FK_F53E53633E1689A FOREIGN KEY (`command_id`) REFERENCES `tc_commands` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
        $this->addSql('ALTER TABLE tc_vehicle_roadworthy_scan ADD CONSTRAINT FK_270D2917545317D1 FOREIGN KEY (`vehicle_id`) REFERENCES `tc_vehicle` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
    }
}
