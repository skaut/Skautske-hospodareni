<?php

declare(strict_types=1);

namespace App\Components\Grids;

use Codeception\Test\Unit;
use Ublaboo\DataGrid\Utils\Sorting;

use function array_map;

final class DtoListDataSourceTest extends Unit
{
    public function testSortsTextColumnsByCzechAlphabet(): void
    {
        $dataSource = new DtoListDataSource([
            new SortableItem('David'),
            new SortableItem('Čeněk'),
            new SortableItem('Ciryl'),
            new SortableItem('Bořek'),
            new SortableItem('Adam'),
        ]);

        $dataSource->sort(new Sorting(['name' => 'ASC']));

        $this->assertSame(
            ['Adam', 'Bořek', 'Ciryl', 'Čeněk', 'David'],
            array_map(static fn (SortableItem $item): string => $item->getName(), $dataSource->getData()),
        );
    }
}

final class SortableItem
{
    public function __construct(private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
