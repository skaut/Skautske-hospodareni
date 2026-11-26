<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261107120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Count the runs that died holding a task apart from its attempts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task_queue_item ADD abandoned SMALLINT UNSIGNED DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task_queue_item DROP abandoned');
    }
}
