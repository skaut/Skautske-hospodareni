<?php

declare(strict_types=1);

namespace Model\User\ReadModel\QueryHandlers;

use Mockery as m;
use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\Unit;
use Model\User\ReadModel\Queries\EditableUnitsQuery;
use Model\User\SkautisRole;
use function array_map;
use function count;

final class EditableUnitsQueryHandlerTest extends \Codeception\Test\Unit
{
    /**
     * parent unit ID => sub units ID
     */
    private const UNITS_TREE = [
        100 => [101, 102],
        101 => [103],
        102 => [],
        103 => [],
    ];

    /** @var EditableUnitsQueryHandler */
    private $handler;

    /**
     * @param int[] $expectedUnitIdsInResult
     *
     * @dataProvider getExpectedReturnedUnits
     */
    public function test(string $roleName, array $expectedUnitIdsInResult) : void
    {
        $role = new SkautisRole($roleName, 100);

        $result =$this->handler->__invoke(new EditableUnitsQuery($role));

        $this->assertCount(count($expectedUnitIdsInResult), $result);

        $index = 0;

        foreach ($result as $id => $unit) {
            $this->assertSame($id, $unit->getId());
            $this->assertSame($expectedUnitIdsInResult[$index], $id);

            $index++;
        }
    }

    /**
     * @return mixed[]
     */
    public function getExpectedReturnedUnits() : array
    {
        return [
            ['cinovnikStredisko', []],
            ['vedouciStredisko', [100, 101, 103, 102]],
            ['hospodarStredisko', [100, 101, 103, 102]],
            ['hospodarOddil', [100, 101, 103, 102]],
            ['hospodarStredisko', [100, 101, 103, 102]],
            ['vedouciDruzina', [100]],
            ['', []],
        ];
    }

    protected function _before() : void
    {
        $unitsRepository = m::mock(IUnitRepository::class);

        foreach (self::UNITS_TREE as $parentUnitId => $subUnitIds) {
            $subUnits = array_map(static function (int $id) : Unit {
                return m::mock(Unit::class, ['getId' => $id]);
            }, $subUnitIds);

            $unitsRepository->shouldReceive('findByParent')
                ->withArgs([$parentUnitId])
                ->andReturn($subUnits);

            $unitsRepository->shouldReceive('find')
                ->withArgs([$parentUnitId])
                ->andReturn(m::mock(Unit::class, ['getId' => $parentUnitId]));
        }

        $this->handler = new EditableUnitsQueryHandler($unitsRepository);
    }
}
