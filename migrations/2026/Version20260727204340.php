<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sjednocuje DB schéma s DBAL 4 / ORM 3.
 *
 * DBAL 4 zrušilo DC2Type komentáře – typ sloupce se určuje výhradně z ORM mapování, takže
 * komentáře `(DC2Type:*)` z historických migrací se odstraňují (u `pa_group.groupType` zůstává
 * jen původní popisný text). Zároveň se dorovnávají dvě reálné odchylky, které komparátor DBAL 3
 * neuměl detekovat: `pa_payment.amount` FLOAT -> DOUBLE PRECISION (mapování má `float`) a
 * `ac_unit_cashbooks.cashbook_id` VARCHAR(36) -> CHAR(36) (guid). Hodnoty v datech se nemění.
 */
final class Version20260727204340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align schema with DBAL 4 (drop DC2Type comments, fix float/guid column types)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ac_camp_cashbooks CHANGE id id CHAR(36) NOT NULL, CHANGE cashbook_id cashbook_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE ac_cashbook CHANGE id id CHAR(36) NOT NULL, CHANGE type type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ac_chit_scan CHANGE file_path file_path VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ac_chits CHANGE eventId eventId CHAR(36) NOT NULL, CHANGE num num VARCHAR(5) DEFAULT NULL, CHANGE date date DATE NOT NULL, CHANGE recipient recipient VARCHAR(64) DEFAULT NULL, CHANGE payment_method payment_method VARCHAR(13) NOT NULL');
        $this->addSql('ALTER TABLE ac_chitsCategory CHANGE operation_type operation_type VARCHAR(64) NOT NULL');
        $this->addSql('ALTER TABLE ac_chitsCategory_object CHANGE type type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE ac_chits_item CHANGE category_operation_type category_operation_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ac_education_cashbooks CHANGE id id CHAR(36) NOT NULL, CHANGE cashbook_id cashbook_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE ac_event_cashbooks CHANGE id id CHAR(36) NOT NULL, CHANGE cashbook_id cashbook_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE ac_participants CHANGE id id CHAR(36) NOT NULL, CHANGE payment payment INT NOT NULL, CHANGE repayment repayment INT NOT NULL, CHANGE event_type event_type VARCHAR(9) NOT NULL');
        $this->addSql('ALTER TABLE ac_unit_budget_category CHANGE type type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ac_unit_cashbooks CHANGE unit_id unit_id CHAR(36) NOT NULL, CHANGE cashbook_id cashbook_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE ac_units CHANGE id id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE admin_user CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE bank_transaction CHANGE date date DATETIME NOT NULL, CHANGE imported_at imported_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE bank_transaction_import_batch CHANGE imported_at imported_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE bank_transaction_pairing CHANGE paired_at paired_at DATETIME NOT NULL, CHANGE cancelled_at cancelled_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE google_oauth CHANGE id id CHAR(36) NOT NULL, CHANGE unit_id unit_id CHAR(36) NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE invoice CHANGE due_date due_date DATETIME NOT NULL, CHANGE date_of_issue date_of_issue DATETIME NOT NULL, CHANGE date_of_tax_payment date_of_tax_payment DATETIME NOT NULL, CHANGE state state VARCHAR(20) NOT NULL, CHANGE variable_symbol variable_symbol VARCHAR(10) NOT NULL, CHANGE closed_at closed_at DATETIME DEFAULT NULL, CHANGE payment_type payment_type VARCHAR(20) NOT NULL, CHANGE sent_at sent_at DATETIME DEFAULT NULL, CHANGE date date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice_access_request CHANGE created_at created_at DATETIME NOT NULL, CHANGE resolved_at resolved_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice_access_user CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE invoice_email_recipient CHANGE email_address email_address VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE invoice_item CHANGE price price NUMERIC(15, 2) NOT NULL');
        $this->addSql('ALTER TABLE invoice_sent_email CHANGE time time DATETIME NOT NULL');
        $this->addSql('ALTER TABLE invoice_sequence CHANGE oauth_id oauth_id CHAR(36) DEFAULT NULL, CHANGE last_pairing last_pairing DATETIME DEFAULT NULL, CHANGE state state VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE log CHANGE date date DATETIME NOT NULL, CHANGE type type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE pa_bank_account CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE pa_group CHANGE groupType groupType VARCHAR(20) DEFAULT NULL COMMENT \'typ entity\', CHANGE due_date due_date DATE DEFAULT NULL, CHANGE next_variable_symbol next_variable_symbol VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE last_pairing last_pairing DATETIME DEFAULT NULL, CHANGE oauth_id oauth_id CHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE pa_group_email CHANGE type type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE pa_payment CHANGE amount amount DOUBLE PRECISION NOT NULL, CHANGE due_date due_date DATE NOT NULL, CHANGE variable_symbol variable_symbol VARCHAR(10) DEFAULT NULL, CHANGE closed_at closed_at DATETIME DEFAULT NULL, CHANGE state state VARCHAR(20) NOT NULL, CHANGE date date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE pa_payment_email_recipients CHANGE email_address email_address VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE pa_payment_sent_emails CHANGE type type VARCHAR(255) NOT NULL, CHANGE time time DATETIME NOT NULL');
        $this->addSql('ALTER TABLE payment_group_visit CHANGE visited_at visited_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tc_commands CHANGE fuel_price fuel_price INT NOT NULL, CHANGE amortization amortization INT NOT NULL, CHANGE closed_at closed_at DATETIME DEFAULT NULL, CHANGE transport_types transport_types JSON NOT NULL');
        $this->addSql('ALTER TABLE tc_contracts CHANGE driver_birthday driver_birthday DATE DEFAULT NULL, CHANGE since since DATE DEFAULT NULL, CHANGE until until DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE tc_travels CHANGE start_date start_date DATE NOT NULL, CHANGE transport_type transport_type VARCHAR(255) NOT NULL, CHANGE price price INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tc_vehicle CHANGE metadata_created_at metadata_created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tc_vehicle_roadworthy_scan CHANGE file_path file_path VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE technical_error_report CHANGE created_at created_at DATETIME NOT NULL, CHANGE notification_sent_at notification_sent_at DATETIME DEFAULT NULL, CHANGE resolved_at resolved_at DATETIME DEFAULT NULL, CHANGE resolution_notification_sent_at resolution_notification_sent_at DATETIME DEFAULT NULL, CHANGE github_issue_created_at github_issue_created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE technical_error_report_reply CHANGE sent_at sent_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user_preference CHANGE updated_at updated_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ac_camp_cashbooks CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:skautis_camp_id)\', CHANGE cashbook_id cashbook_id CHAR(36) NOT NULL COMMENT \'(DC2Type:cashbook_id)\'');
        $this->addSql('ALTER TABLE ac_cashbook CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:cashbook_id)\', CHANGE type type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:cashbook_type)\'');
        $this->addSql('ALTER TABLE ac_chit_scan CHANGE file_path file_path VARCHAR(255) NOT NULL COMMENT \'(DC2Type:file_path)\'');
        $this->addSql('ALTER TABLE ac_chits CHANGE payment_method payment_method VARCHAR(13) NOT NULL COMMENT \'(DC2Type:chit_payment_method)\', CHANGE num num VARCHAR(5) DEFAULT NULL COMMENT \'(DC2Type:chit_number)\', CHANGE date date DATE NOT NULL COMMENT \'(DC2Type:chronos_date)\', CHANGE recipient recipient VARCHAR(64) DEFAULT NULL COMMENT \'(DC2Type:recipient)\', CHANGE eventId eventId CHAR(36) NOT NULL COMMENT \'(DC2Type:cashbook_id)\'');
        $this->addSql('ALTER TABLE ac_chitsCategory CHANGE operation_type operation_type VARCHAR(64) NOT NULL COMMENT \'(DC2Type:cashbook_operation)\'');
        $this->addSql('ALTER TABLE ac_chitsCategory_object CHANGE type type VARCHAR(20) NOT NULL COMMENT \'(DC2Type:cashbook_object_type)\'');
        $this->addSql('ALTER TABLE ac_chits_item CHANGE category_operation_type category_operation_type VARCHAR(255) DEFAULT NULL COMMENT \'(DC2Type:cashbook_operation)\'');
        $this->addSql('ALTER TABLE ac_education_cashbooks CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:skautis_education_id)\', CHANGE cashbook_id cashbook_id CHAR(36) NOT NULL COMMENT \'(DC2Type:cashbook_id)\'');
        $this->addSql('ALTER TABLE ac_event_cashbooks CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:skautis_event_id)\', CHANGE cashbook_id cashbook_id CHAR(36) NOT NULL COMMENT \'(DC2Type:cashbook_id)\'');
        $this->addSql('ALTER TABLE ac_participants CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:payment_id)\', CHANGE payment payment INT NOT NULL COMMENT \'(DC2Type:money)\', CHANGE repayment repayment INT NOT NULL COMMENT \'(DC2Type:money)\', CHANGE event_type event_type VARCHAR(9) NOT NULL COMMENT \'(DC2Type:participant_event_type)\'');
        $this->addSql('ALTER TABLE ac_unit_budget_category CHANGE type type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:cashbook_operation)\'');
        $this->addSql('ALTER TABLE ac_unit_cashbooks CHANGE cashbook_id cashbook_id VARCHAR(36) NOT NULL COMMENT \'(DC2Type:cashbook_id)\', CHANGE unit_id unit_id CHAR(36) NOT NULL COMMENT \'(DC2Type:unit_id)\'');
        $this->addSql('ALTER TABLE ac_units CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:unit_id)\'');
        $this->addSql('ALTER TABLE admin_user CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE bank_transaction CHANGE date date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE imported_at imported_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE bank_transaction_import_batch CHANGE imported_at imported_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE bank_transaction_pairing CHANGE paired_at paired_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE cancelled_at cancelled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE google_oauth CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:oauth_id)\', CHANGE unit_id unit_id CHAR(36) NOT NULL COMMENT \'(DC2Type:unit_id)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE invoice CHANGE due_date due_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE date_of_issue date_of_issue DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE date_of_tax_payment date_of_tax_payment DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE state state VARCHAR(20) NOT NULL COMMENT \'(DC2Type:string_enum)\', CHANGE variable_symbol variable_symbol VARCHAR(10) NOT NULL COMMENT \'(DC2Type:variable_symbol)\', CHANGE closed_at closed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE payment_type payment_type VARCHAR(20) NOT NULL COMMENT \'(DC2Type:string_enum)\', CHANGE sent_at sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE date date DATE DEFAULT NULL COMMENT \'(DC2Type:chronos_date)\'');
        $this->addSql('ALTER TABLE invoice_access_request CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE resolved_at resolved_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE invoice_access_user CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE invoice_email_recipient CHANGE email_address email_address VARCHAR(255) NOT NULL COMMENT \'(DC2Type:email_address)\'');
        $this->addSql('ALTER TABLE invoice_item CHANGE price price NUMERIC(15, 2) NOT NULL COMMENT \'(DC2Type:big_decimal)\'');
        $this->addSql('ALTER TABLE invoice_sent_email CHANGE time time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE invoice_sequence CHANGE oauth_id oauth_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:oauth_id)\', CHANGE last_pairing last_pairing DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE state state VARCHAR(20) NOT NULL COMMENT \'(DC2Type:string_enum)\'');
        $this->addSql('ALTER TABLE log CHANGE date date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE type type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:log_type)\'');
        $this->addSql('ALTER TABLE pa_bank_account CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE pa_group CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE oauth_id oauth_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:oauth_id)\', CHANGE groupType groupType VARCHAR(20) DEFAULT NULL COMMENT \'typ entity(DC2Type:payment_group_type)\', CHANGE due_date due_date DATE DEFAULT NULL COMMENT \'(DC2Type:chronos_date)\', CHANGE next_variable_symbol next_variable_symbol VARCHAR(255) DEFAULT NULL COMMENT \'(DC2Type:variable_symbol)\', CHANGE last_pairing last_pairing DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE pa_group_email CHANGE type type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:payment_email_type)\'');
        $this->addSql('ALTER TABLE pa_payment CHANGE amount amount FLOAT NOT NULL, CHANGE due_date due_date DATE NOT NULL COMMENT \'(DC2Type:chronos_date)\', CHANGE variable_symbol variable_symbol VARCHAR(10) DEFAULT NULL COMMENT \'(DC2Type:variable_symbol)\', CHANGE closed_at closed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE state state VARCHAR(20) NOT NULL COMMENT \'(DC2Type:payment_state)\', CHANGE date date DATE DEFAULT NULL COMMENT \'(DC2Type:chronos_date)\'');
        $this->addSql('ALTER TABLE pa_payment_email_recipients CHANGE email_address email_address VARCHAR(255) NOT NULL COMMENT \'(DC2Type:email_address)\'');
        $this->addSql('ALTER TABLE pa_payment_sent_emails CHANGE type type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:payment_email_type)\', CHANGE time time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE payment_group_visit CHANGE visited_at visited_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE tc_commands CHANGE fuel_price fuel_price INT NOT NULL COMMENT \'(DC2Type:money)\', CHANGE amortization amortization INT NOT NULL COMMENT \'(DC2Type:money)\', CHANGE closed_at closed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE transport_types transport_types JSON NOT NULL COMMENT \'(DC2Type:transport_types)\'');
        $this->addSql('ALTER TABLE tc_contracts CHANGE since since DATE DEFAULT NULL COMMENT \'(DC2Type:chronos_date)\', CHANGE until until DATE DEFAULT NULL COMMENT \'(DC2Type:chronos_date)\', CHANGE driver_birthday driver_birthday DATE DEFAULT NULL COMMENT \'(DC2Type:chronos_date)\'');
        $this->addSql('ALTER TABLE tc_travels CHANGE start_date start_date DATE NOT NULL COMMENT \'(DC2Type:chronos_date)\', CHANGE transport_type transport_type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:travel_transport_type)\', CHANGE price price INT DEFAULT NULL COMMENT \'(DC2Type:money)\'');
        $this->addSql('ALTER TABLE tc_vehicle CHANGE metadata_created_at metadata_created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE tc_vehicle_roadworthy_scan CHANGE file_path file_path VARCHAR(255) NOT NULL COMMENT \'(DC2Type:file_path)\'');
        $this->addSql('ALTER TABLE technical_error_report CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE notification_sent_at notification_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE resolved_at resolved_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE resolution_notification_sent_at resolution_notification_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE github_issue_created_at github_issue_created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE technical_error_report_reply CHANGE sent_at sent_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_preference CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
