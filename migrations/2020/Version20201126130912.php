<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201126130912 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Fix typo in name of custom type';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql("ALTER TABLE google_oauth MODIFY COLUMN id CHAR(36) NOT NULL COMMENT '(DC2Type:oauth_id)'");
        $this->addSql("ALTER TABLE pa_group MODIFY COLUMN oauth_id CHAR(36) DEFAULT NULL COMMENT '(DC2Type:oauth_id)'");
    }

    public function down(Schema $schema) : void
    {
        $this->addSql("ALTER TABLE google_oauth MODIFY COLUMN id CHAR(36) NOT NULL COMMENT '(DC2Type:ouath_id)'");
        $this->addSql("ALTER TABLE pa_group MODIFY COLUMN oauth_id CHAR(36) DEFAULT NULL COMMENT '(DC2Type:ouath_id)'");
    }
}
