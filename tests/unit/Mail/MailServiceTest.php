<?php

declare(strict_types=1);

namespace Model;

use Codeception\Test\Unit;
use Entity\GoogleOAuth;
use Mockery as m;
use Model\Common\UnitId;
use Model\Mail\Repositories\IGoogleRepository;
use Model\Payment\IUnitResolver;

final class MailServiceTest extends Unit
{
    public function testGetAllIncludesOfficialUnitsAndFlattensOauths(): void
    {
        $officialOAuth = GoogleOAuth::create(new UnitId(20), 'token', 'official@example.com');

        $repository = m::mock(IGoogleRepository::class);
        $repository->shouldReceive('findByUnits')
            ->once()
            ->with([10, 20])
            ->andReturn([
                10 => [],
                20 => [$officialOAuth],
            ]);

        $unitResolver = new class() implements IUnitResolver
        {
            public function getOfficialUnitId(int $unitId): int
            {
                return $unitId === 10 ? 20 : $unitId;
            }
        };

        $result = (new MailService($repository, $unitResolver))->getAll([10]);

        self::assertCount(1, $result);
        self::assertSame('official@example.com', $result[0]->getEmail());
        self::assertSame(20, $result[0]->getUnitId());
    }
}
