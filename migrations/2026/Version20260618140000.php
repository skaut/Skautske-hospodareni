<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260618140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track the source payment for split payments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pa_payment ADD split_from_payment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pa_payment ADD CONSTRAINT FK_2299C210E0231CCA FOREIGN KEY (split_from_payment_id) REFERENCES pa_payment (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_2299C210E0231CCA ON pa_payment (split_from_payment_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pa_payment DROP FOREIGN KEY FK_2299C210E0231CCA');
        $this->addSql('DROP INDEX IDX_2299C210E0231CCA ON pa_payment');
        $this->addSql('ALTER TABLE pa_payment DROP split_from_payment_id');
    }
}
