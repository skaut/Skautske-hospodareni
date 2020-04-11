<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ramsey\Uuid\Uuid;
use Throwable;

final class Version20200411105206 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Update cashbook int IDs to UUIDs';
    }

    public function up(Schema $schema) : void
    {
        $cashbookIds = $this->connection->fetchAll('SELECT id FROM `ac_object` WHERE length(id) < 36');
        $this->connection->beginTransaction();
        try {
            foreach ($cashbookIds as $cashbookId) {
                $ids = [Uuid::uuid4()->toString(), $cashbookId['id']];

                $this->connection->executeUpdate('UPDATE ac_object         SET id = ?          WHERE id = ?', $ids);
                $this->connection->executeUpdate('UPDATE ac_cashbook       SET id = ?          WHERE id = ?', $ids);
                $this->connection->executeUpdate('UPDATE ac_chits          SET eventId = ?     WHERE eventId = ?', $ids);
                $this->connection->executeUpdate('UPDATE ac_unit_cashbooks SET cashbook_id = ? WHERE cashbook_id = ?', $ids);
            }
            $this->connection->commit();
        } catch (Throwable $exc) {
            $this->connection->rollBack();
        }
    }

    public function down(Schema $schema) : void
    {
    }
}
