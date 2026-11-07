<?php

declare(strict_types=1);

namespace App\Components\Grids;

use Codeception\Test\Unit;
use Contributte\Datagrid\Datagrid;
use Contributte\Datagrid\Filter\FilterSelect;

final class DataSourceTest extends Unit
{
    /** A grid used outside of its rendering (e.g. by an export) has filters without a value. */
    public function testConditionOfFilterWithoutValueIsNotApplied(): void
    {
        $grid = new Datagrid();
        $applied = [];
        $remember = static function (DataSource $dataSource, mixed $value) use (&$applied): void {
            $applied[] = $value;
        };

        $year = (new FilterSelect($grid, 'year', 'Rok', ['2026' => '2026'], 'year'))->setCondition($remember);
        $state = (new FilterSelect($grid, 'state', 'Stav', ['open' => 'Otevřené'], 'state'))->setCondition($remember);
        $state->setValue('open');

        $dataSource = new class extends DataSource {
            /** @return object[] */
            protected function loadData(): array
            {
                return [];
            }
        };

        $dataSource->filter([$year, $state]);

        self::assertSame(['open'], $applied);
    }
}
