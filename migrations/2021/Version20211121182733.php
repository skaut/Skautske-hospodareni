<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211121182733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unused pa_payment.email column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pa_payment DROP email');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pa_payment ADD email TEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_czech_ci`');
    }
}
