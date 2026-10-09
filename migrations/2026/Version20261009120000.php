<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261009120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Unify cashbook category names and make the common catalogue available to events, camps and education';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE ac_chitsCategory
            SET name = CASE id
                WHEN 1 THEN 'Od dětí a roverů'
                WHEN 2 THEN 'Ostatní služby'
                WHEN 3 THEN 'Potraviny, stravné'
                WHEN 4 THEN 'Doprava osob a materiálu'
                WHEN 5 THEN 'Nájem'
                WHEN 7 THEN 'Převod do pokladny jednotky'
                WHEN 9 THEN 'Převod z pokladny jednotky'
                WHEN 15 THEN 'Převod z pokladny jednotky'
                WHEN 16 THEN 'Převod do pokladny jednotky'
                ELSE name
            END
            WHERE id IN (1, 2, 3, 4, 5, 7, 9, 15, 16)
            SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO ac_chitsCategory (id, name, shortcut, operation_type, `virtual`, priority, deleted) VALUES
                (23, 'Od dospělých', 'adult-income', 'in', 0, 100, 0),
                (24, 'Ostatní výdaje', 'other-expense', 'out', 0, 100, 0),
                (25, 'Vlastní finanční prostředky', 'own-funds', 'in', 0, 100, 0)
            SQL);

        foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 15, 16, 17, 18, 19, 21, 22, 23, 24, 25] as $categoryId) {
            foreach (['general', 'camp', 'education'] as $type) {
                $this->addSql(
                    'INSERT IGNORE INTO ac_chitsCategory_object (category_id, type) VALUES (?, ?)',
                    [$categoryId, $type],
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $addedTypes = [
            1 => ['camp', 'education'],
            2 => ['camp', 'education'],
            3 => ['camp', 'education'],
            4 => ['camp', 'education'],
            5 => ['camp', 'education'],
            6 => ['camp', 'education'],
            7 => ['education'],
            8 => ['education'],
            9 => ['education'],
            10 => ['camp', 'education'],
            12 => ['education'],
            15 => ['general', 'camp', 'education'],
            16 => ['general', 'camp', 'education'],
            17 => ['camp', 'education'],
            18 => ['camp', 'education'],
            19 => ['camp', 'education'],
            21 => ['general', 'education'],
            22 => ['general', 'education'],
        ];
        foreach ($addedTypes as $categoryId => $types) {
            foreach ($types as $type) {
                $this->addSql('DELETE FROM ac_chitsCategory_object WHERE category_id = ? AND type = ?', [$categoryId, $type]);
            }
        }

        foreach ([23, 24, 25] as $categoryId) {
            $this->addSql('DELETE FROM ac_chitsCategory_object WHERE category_id = ?', [$categoryId]);
            $this->addSql('DELETE FROM ac_chitsCategory WHERE id = ?', [$categoryId]);
        }

        $this->addSql(<<<'SQL'
            UPDATE ac_chitsCategory
            SET name = CASE id
                WHEN 1 THEN 'Příjmy od účastníků'
                WHEN 2 THEN 'Služby'
                WHEN 3 THEN 'Potraviny'
                WHEN 4 THEN 'Jízdné'
                WHEN 5 THEN 'Nájemné'
                WHEN 7 THEN 'Převod do stř. pokladny'
                WHEN 9 THEN 'Převod z pokladny střediska'
                WHEN 15 THEN 'Převod z akce'
                WHEN 16 THEN 'Převod do akce'
                ELSE name
            END
            WHERE id IN (1, 2, 3, 4, 5, 7, 9, 15, 16)
            SQL);
    }
}
