<?php

declare(strict_types=1);

namespace Migrations;

use BankAccountValidator\Czech;
use DateTimeImmutable;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Nette\Utils\Strings;
use Skautis\Skautis;
use stdClass;
use function count;

class Version20170623193150 extends AbstractMigration
{
    /**
     * @var Skautis
     * @inject
     */
    public $skautis;

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE pa_bank_account (id INT AUTO_INCREMENT NOT NULL, unit_id INT NOT NULL, name VARCHAR(255) NOT NULL, token VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', allowed_for_subunits TINYINT(1) NOT NULL, number_prefix VARCHAR(6) DEFAULT NULL, number_number VARCHAR(10) NOT NULL, number_bank_code VARCHAR(4) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE pa_group ADD bank_account_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pa_group ADD CONSTRAINT fk_bank_account_id FOREIGN KEY (bank_account_id) REFERENCES pa_bank_account(id)');
    }

    public function postUp(Schema $schema) : void
    {
        $bankConfigurations = $this->connection->fetchAll('SELECT * FROM pa_bank');

        $parser = new Czech();
        $now    = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        if (count($bankConfigurations) > 0) {
            foreach ($bankConfigurations as $configuration) {
                $unitId        = (int) $configuration['unitId'];
                $accountNumber = $this->getMainAccount($unitId);

                if ($accountNumber === null || ! $configuration['token']) {
                    continue;
                }

                $number = $parser->parseNumber($accountNumber);

                $this->connection->insert('pa_bank_account', [
                    'unit_id' => $unitId,
                    'name' => 'Hlavní',
                    'token' => $configuration['token'],
                    'created_at' => $now,
                    'allowed_for_subunits' => 1,
                    'number_prefix' => $number[0],
                    'number_number' => $number[1],
                    'number_bank_code' => $number[2],
                ]);

                $bankAccountId = $this->connection->lastInsertId();
                $this->connection->exec('UPDATE pa_group SET bank_account_id = ' . $bankAccountId . ' WHERE unitId = ' . $unitId);
            }
        }

        $this->connection->exec('DROP TABLE pa_bank');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE pa_bank_account');
        $this->addSql('ALTER TABLE pa_group DROP bank_account_id');
    }

    private function getMainAccount(int $unitId) : ?string
    {
        $result = $this->skautis->org->AccountAll(['ID_Unit' => $unitId]);

        if ($result instanceof stdClass) {
            return null;
        }

        foreach ($result as $account) {
            if ($account->IsMain) {
                return Strings::endsWith($account->DisplayName, '/2010')
                    ? $account->DisplayName
                    : null; // Only FIO is supported
            }
        }

        return null;
    }
}
