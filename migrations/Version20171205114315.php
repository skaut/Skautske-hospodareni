<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Payment\IUnitResolver;

class Version20171205114315 extends AbstractMigration
{

    /** @var IUnitResolver @inject */
    public $unitResolver;

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ac_cashbook ADD type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:string_enum)\'');
        $this->addSql('UPDATE ac_cashbook c JOIN ac_object o ON o.id = c.id SET c.type = o.type');
    }

    public function postUp(Schema $schema): void
    {
        $unitCashbooks = $this->connection->fetchAll(
            "SELECT c.id as id, o.skautisId as unit_id FROM ac_cashbook c JOIN ac_object o ON o.id = c.id WHERE c.type = 'unit'"
        );

        foreach($unitCashbooks as $cashbook) {
            $cashbook['unit_id'] = (int) $cashbook['unit_id'];
            $isOfficialUnit = $this->unitResolver->getOfficialUnitId($cashbook['unit_id']) === $cashbook['unit_id'];
            $this->connection->update('ac_cashbook', [
                'type' => $isOfficialUnit ? CashbookType::OFFICIAL_UNIT : CashbookType::TROOP,
            ], ['id' => $cashbook['id']]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ac_cashbook DROP type');
    }

}
