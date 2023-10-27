<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

use function array_merge;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

final class Version20231027085506 extends AbstractMigration
{
    /** @var array<string> */
    private array $tables_utf8mb3_czech_ci = [
        'ac_cashbook',
        'ac_chits',
        'ac_chitsCategory',
        'ac_chits_item',
        'ac_participants',
        'ac_unit_budget_category',
        'ac_unit_cashbooks',
        'ac_units',
        'pa_group',
        'pa_payment',
        'tc_commands',
        'tc_contracts',
        'tc_travels',
        'tc_vehicle',
    ];

    /** @var array<string> */
    private array $tables_utf8mb3_general_ci = ['ac_chitsCategory_object'];

    /** @var array<string> */
    private array $tables_utf8mb3_unicode_ci = [
        'ac_camp_cashbooks',
        'ac_chit_scan',
        'ac_chit_to_item',
        'ac_education_cashbooks',
        'ac_event_cashbooks',
        'doctrine_migrations',
        'google_oauth',
        'pa_bank_account',
        'pa_group_email',
        'pa_group_unit',
        'pa_payment_email_recipients',
        'pa_payment_sent_emails',
        'tc_vehicle_roadworthy_scan',
    ];

    /** @var array<string> */
    private array $tables_latin1_swedish_ci = ['log'];

    public function getDescription(): string
    {
        return 'Sjednocení charsetů a collation celé databáze';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci;');

        $this->addSql('SET foreign_key_checks = 0;');

        foreach (
            array_merge(
                $this->tables_utf8mb3_czech_ci,
                $this->tables_utf8mb3_general_ci,
                $this->tables_utf8mb3_unicode_ci,
                $this->tables_latin1_swedish_ci,
            ) as $table
        ) {
            $this->addSql('ALTER TABLE `' . $table . '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci;');
        }

        $this->addSql('SET foreign_key_checks = 1;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET foreign_key_checks = 0;');

        foreach ($this->tables_utf8mb3_czech_ci as $table) {
            $this->addSql('ALTER TABLE `' . $table . '` CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci;');
        }

        foreach ($this->tables_utf8mb3_general_ci as $table) {
            $this->addSql('ALTER TABLE `' . $table . '` CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci;');
        }

        foreach ($this->tables_utf8mb3_unicode_ci as $table) {
            $this->addSql('ALTER TABLE `' . $table . '` CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci;');
        }

        foreach ($this->tables_latin1_swedish_ci as $table) {
            $this->addSql('ALTER TABLE `' . $table . '` CONVERT TO CHARACTER SET latin1 COLLATE latin1_swedish_ci;');
        }

        $this->addSql('SET foreign_key_checks = 1;');
    }
}
