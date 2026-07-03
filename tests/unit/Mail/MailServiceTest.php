<?php

declare(strict_types=1);

namespace App\Model\Mail;

use App\Model\Common\UnitId;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Mail\Repositories\IGoogleRepository;
use App\Model\Payment\IUnitResolver;
use Codeception\Test\Unit;
use Mockery as m;

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

        $unitResolver = new class implements IUnitResolver {
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
