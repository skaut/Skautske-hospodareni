<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201128164940 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Allow more recipients for payments';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
        CREATE TABLE pa_payment_email_recipients (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            payment_id INT DEFAULT NULL,
            email_address VARCHAR(255) NOT NULL COMMENT '(DC2Type:email_address)',
            INDEX IDX_A3FBD6514C3A3BB (payment_id),
            PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB
SQL);
        $this->addSql('ALTER TABLE pa_payment_email_recipients ADD CONSTRAINT FK_A3FBD6514C3A3BB FOREIGN KEY (payment_id) REFERENCES pa_payment (id)');
    }

    public function postUp(Schema $schema) : void
    {
        $paymentsPairs = $this->connection->fetchAllKeyValue('SELECT id, email FROM pa_payment');
        foreach ($paymentsPairs as $paymentId => $email) {
            if ($email === null) {
                continue;
            }

            $this->connection->insert('pa_payment_email_recipients', [
                'payment_id' => $paymentId,
                'email_address' => $email,
            ]);
        }
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE pa_payment_email_recipients');
    }
}
