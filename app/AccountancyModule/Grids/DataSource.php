<?php

declare(strict_types=1);

namespace App\AccountancyModule\Grids;

use Doctrine\Common\Collections\ArrayCollection;
use Ublaboo\DataGrid\DataSource\DoctrineCollectionDataSource;
use Ublaboo\DataGrid\DataSource\IDataSource;
use Ublaboo\DataGrid\Filter\Filter;
use Ublaboo\DataGrid\Utils\Sorting;

use function count;

abstract class DataSource implements IDataSource
{
    private Sorting|null $sorting = null;

    private int|null $offset = null;

    private int|null $limit = null;

    private DoctrineCollectionDataSource|null $innerDataSource = null;

    /** @var Filter[] */
    private array $filters = [];

    /** @var mixed[] */
    private array $conditions = [];

    /** @return object[] */
    abstract protected function loadData(): array;

    public function getCount(): int
    {
        return count($this->getData());
    }

    /** @return mixed[] */
    final public function getData(): array
    {
        $dataSource = $this->innerDataSource();

        $dataSource->filter($this->filters);
        foreach ($this->conditions as $condition) {
            $dataSource->filterOne($condition);
        }

        if ($this->sorting) {
            $dataSource->sort($this->sorting);
        }

        if ($this->limit !== null) {
            $dataSource->limit($this->offset, $this->limit);
        }

        return $dataSource->getData();
    }

    /** @param Filter[] $filters */
    final public function filter(array $filters): void
    {
        foreach ($filters as $filter) {
            $callback = $filter->getConditionCallback();

            if ($callback !== null) {
                $callback($this, $filter->getValue());
                continue;
            }

            $this->filters[] = $filter;
        }
    }

    /** @param mixed[] $filter */
    final public function filterOne(array $filter): self
    {
        $this->conditions[] = $filter;

        return $this;
    }

    final public function limit(int $offset, int $limit): self
    {
        $this->offset = $offset;
        $this->limit  = $limit;

        return $this;
    }

    final public function sort(Sorting $sorting): self
    {
        $this->sorting = $sorting;

        return $this;
    }

    private function innerDataSource(): IDataSource
    {
        if ($this->innerDataSource === null) {
            $this->innerDataSource = new DoctrineCollectionDataSource(new ArrayCollection($this->loadData()), 'id');
        }

        return $this->innerDataSource;
    }
}
