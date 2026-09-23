<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dashboard announcements and normalized categories';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE announcement_category (code VARCHAR(16) NOT NULL, PRIMARY KEY(code)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");
        $this->addSql("INSERT INTO announcement_category (code) VALUES ('news'), ('info'), ('warning'), ('error'), ('plan')");
        $this->addSql("CREATE TABLE announcement (id INT UNSIGNED AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, message VARCHAR(600) NOT NULL, category_code VARCHAR(16) NOT NULL, published_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', hidden TINYINT(1) DEFAULT 0 NOT NULL, INDEX announcement_visible_published_idx (hidden, published_at, expires_at), INDEX IDX_ANNOUNCEMENT_CATEGORY (category_code), PRIMARY KEY(id), CONSTRAINT FK_ANNOUNCEMENT_CATEGORY FOREIGN KEY (category_code) REFERENCES announcement_category (code) ON DELETE RESTRICT) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE announcement');
        $this->addSql('DROP TABLE announcement_category');
    }
}
