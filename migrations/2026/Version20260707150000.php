<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment group visit history per user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE payment_group_visit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED NOT NULL, group_id INT UNSIGNED NOT NULL, visited_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX payment_group_visit_user_group_unique (user_id, group_id), INDEX payment_group_visit_user_visited_idx (user_id, visited_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_czech_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE payment_group_visit');
    }
}
