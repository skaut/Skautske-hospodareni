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
            ->andReturn($this->unit(1, '1', 'Ústředí', 'ustredi'));
        $repository->shouldReceive('findByParent')
            ->once()
            ->with(1)
            ->andReturn([
                $this->unit(2, '2', 'Kraj', 'kraj'),
            ]);
        $repository->shouldReceive('findByParent')
            ->once()
            ->with(2)
            ->andReturn([
                $this->unit(3, '2.01', 'Středisko', 'stredisko'),
            ]);

        self::assertSame([
            1 => '1 Ústředí',
            2 => '   2 Kraj',
            3 => '      2.01 Středisko',
        ], (new UnitTreeSelectOptions($repository))->getOptions(1));
    }

    /** One skautIS call per troop made the whole tree take minutes on an empty cache. */
    public function testTroopsOfStrediskoAreNotLoaded(): void
    {
        $repository = Mockery::mock(IUnitRepository::class);
        $repository->shouldReceive('find')->once()->with(1)->andReturn($this->unit(1, '1', 'Ústředí', 'ustredi'));
        $repository->shouldReceive('findByParent')->once()->with(1)->andReturn([
            $this->unit(2, '2.01', 'Středisko', 'stredisko'),
            $this->unit(3, '2.02', 'Oddíl přímo pod ústředím', 'oddil'),
        ]);
        $repository->shouldNotReceive('findByParent')->with(2);
        $repository->shouldNotReceive('findByParent')->with(3);

        self::assertSame([
            1 => '1 Ústředí',
            2 => '   2.01 Středisko',
            3 => '   2.02 Oddíl přímo pod ústředím',
        ], (new UnitTreeSelectOptions($repository))->getOptions(1));
    }

    public function testSelectedTroopIsAddedSoTheFormKeepsIt(): void
    {
        $repository = Mockery::mock(IUnitRepository::class);
        $repository->shouldReceive('find')->once()->with(1)->andReturn($this->unit(1, '1', 'Ústředí', 'ustredi'));
        $repository->shouldReceive('find')->once()->with(9)->andReturn($this->unit(9, '2.01.1', 'Oddíl', 'oddil'));
        $repository->shouldReceive('findByParent')->once()->with(1)->andReturn([
            $this->unit(2, '2.01', 'Středisko', 'stredisko'),
        ]);

        self::assertSame([
            1 => '1 Ústředí',
            2 => '   2.01 Středisko',
            9 => '2.01.1 Oddíl',
        ], (new UnitTreeSelectOptions($repository))->getOptions(1, 9));
    }

    private function unit(int $id, string $registrationNumber, string $displayName, string $type): Unit
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
            $type,
        );
    }
}
