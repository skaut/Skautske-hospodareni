<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250915150543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix doctrine mapping';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ac_cashbook CHANGE note note LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE log CHANGE description description LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ac_cashbook CHANGE note note MEDIUMTEXT NOT NULL');
        $this->addSql('ALTER TABLE log CHANGE description description MEDIUMTEXT NOT NULL');
    }
}
