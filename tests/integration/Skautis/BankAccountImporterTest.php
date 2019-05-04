<?php

declare(strict_types=1);

namespace Model\Skautis;

use Hskauting\Tests\SkautisTest;
use VCR\VCR;

final class BankAccountImporterTest extends SkautisTest
{
    public function testImportReturnsEmptyListWhenUnitHasNoBankAccounts() : void
    {
        VCR::insertCassette('BankAccountImporter/import_empty.json');
        $skautis  = $this->createSkautis('349a8cc1-f893-4e08-927e-8ee4dab26c93');
        $importer = new BankAccountImporter($skautis);

        $accountNumbers = $importer->import(27266);

        self::assertCount(0, $accountNumbers);
    }

    public function testImportReturnsAccountNumbers() : void
    {
        VCR::insertCassette('BankAccountImporter/import.json');
        $skautis  = $this->createSkautis('349a8cc1-f893-4e08-927e-8ee4dab26c93');
        $importer = new BankAccountImporter($skautis);

        $accountNumbers = $importer->import(27266);

        self::assertCount(1, $accountNumbers);

        self::assertSame('2000942144/2010', (string) $accountNumbers[0]);
    }
}
