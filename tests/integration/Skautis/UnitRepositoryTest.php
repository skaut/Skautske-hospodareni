<?php

declare(strict_types=1);

namespace Model\Skautis;

use Hskauting\Tests\SkautisTest;
use Model\Unit\Unit;
use Model\Unit\UnitNotFound;
use VCR\VCR;

final class UnitRepositoryTest extends SkautisTest
{
    public function testFindThrowsExceptionIfUnitDoesNotExist() : void
    {
        VCR::insertCassette('UnitRepository/find.json');

        $this->expectException(UnitNotFound::class);

        $this->getRepository()->find(0);
    }

    public function testFindReturnsCorrectUnitInstance() : void
    {
        VCR::insertCassette('UnitRepository/find_nonexistent.json');

        $unit = $this->getRepository()->find(27266);

        self::assertUnitMatchesExpectedData($unit);
    }

    public function testFindByParent() : void
    {
        VCR::insertCassette('UnitRepository/findByParent.json');

        $units = $this->getRepository()->findByParent(23506);
        self::assertCount(1, $units);
        self::assertUnitMatchesExpectedData($units[0]);
    }

    public function testFindByParentReturnsEmptyListIfNothingIsReturned() : void
    {
        VCR::insertCassette('UnitRepository/findByParent_empty.json');

        self::assertSame([], $this->getRepository()->findByParent(0));
    }

    private function getRepository() : UnitRepository
    {
        $skautis = $this->createSkautis('c33aa563-68eb-4697-9834-ec2eb56d17d1');

        return new UnitRepository($skautis->getWebService('org'));
    }

    private static function assertUnitMatchesExpectedData(Unit $unit) : void
    {
        self::assertSame(27266, $unit->getId());
        self::assertSame('621.66 - Sinovo středisko', $unit->getSortName());
        self::assertSame('Sinovo středisko', $unit->getDisplayName());
        self::assertSame('25596641', $unit->getIc());
        self::assertSame('25596641', $unit->getIc());
        self::assertSame('K Sinoru 53/42', $unit->getStreet());
        self::assertSame('621.66', $unit->getRegistrationNumber());
        self::assertTrue($unit->isOfficial());
        self::assertSame(23506, $unit->getParentId());
    }
}
