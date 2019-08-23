<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components;

final class DataGrid extends \Ublaboo\DataGrid\DataGrid
{
    /**
     * Forces datagrid to filter and sort data source and returns inner data
     *
     * @return mixed[]
     */
    public function getFilteredAndSortedData() : array
    {
        return $this->dataModel->filterData(
            $this->getPaginator(),
            $this->createSorting($this->sort, $this->sort_callback),
            $this->assembleFilters()
        );
    }
}
