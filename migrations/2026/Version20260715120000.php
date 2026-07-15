<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store requester email for invoice access requests';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice_access_request ADD requester_email VARCHAR(255) DEFAULT NULL AFTER display_name');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice_access_request DROP requester_email');
    }
}
