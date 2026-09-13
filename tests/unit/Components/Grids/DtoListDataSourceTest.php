<?php

declare(strict_types=1);

namespace App\Components\Grids;

use Codeception\Test\Unit;
use Contributte\Datagrid\Utils\Sorting;

use function array_map;

final class DtoListDataSourceTest extends Unit
{
    /** @return list<string> */
    public function getCzechAlphabetSortOrder(): array
    {
        return ['a', 'b', 'c', 'č', 'd', 'ď', 'e', 'ě', 'f', 'g', 'h', 'ch', 'i', 'j', 'k', 'l', 'm', 'n', 'ň', 'o', 'p', 'q', 'r', 'ř', 's', 'š', 't', 'ť', 'u', 'ů', 'v', 'w', 'x', 'y', 'z', 'ž'];
    }

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

    public function testSortsTextColumnsByCzechAlphabetLetters(): void
    {
        $expectedOrder = $this->getCzechAlphabetSortOrder();
        $dataSource = new DtoListDataSource(
            array_map(
                static fn (string $letter): SortableItem => new SortableItem($letter),
                [...$expectedOrder],
            ),
        );

        $dataSource->sort(new Sorting(['name' => 'DESC']));
        $dataSource->sort(new Sorting(['name' => 'ASC']));

        $this->assertSame(
            $expectedOrder,
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
