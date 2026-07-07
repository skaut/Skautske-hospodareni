<?php

declare(strict_types=1);

namespace App\Model\Unit\Services;

use App\Model\Unit\Repositories\IUnitRepository;
use App\Model\Unit\Unit;
use Codeception\Test\Unit as TestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

final class UnitTreeSelectOptionsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testOptionsContainNestedUnitTreeWithoutSkautisUserAccessFilter(): void
    {
        $repository = Mockery::mock(IUnitRepository::class);
        $repository->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($this->unit(1, '1', 'Ústředí'));
        $repository->shouldReceive('findByParent')
            ->once()
            ->with(1)
            ->andReturn([
                $this->unit(2, '2', 'Kraj'),
            ]);
        $repository->shouldReceive('findByParent')
            ->once()
            ->with(2)
            ->andReturn([
                $this->unit(3, '2.01', 'Středisko'),
            ]);
        $repository->shouldReceive('findByParent')
            ->once()
            ->with(3)
            ->andReturn([]);

        self::assertSame([
            1 => '1 Ústředí',
            2 => '   2 Kraj',
            3 => '      2.01 Středisko',
        ], (new UnitTreeSelectOptions($repository))->getOptions(1));
    }

    private function unit(int $id, string $registrationNumber, string $displayName): Unit
    {
        return new Unit(
            $id,
            $registrationNumber.' '.$displayName,
            $displayName,
            null,
            '',
            '',
            '',
            $registrationNumber,
            'stredisko',
        );
    }
}
