<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190103180852 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE pa_payment CHANGE ks ks SMALLINT UNSIGNED DEFAULT NULL;');
    }

    public function down(Schema $schema) : void
    {
    }
}
