<?php

declare(strict_types=1);

namespace App\AccountancyModule\Grids;

use Doctrine\Common\Collections\ArrayCollection;
use Ublaboo\DataGrid\DataSource\DoctrineCollectionDataSource;
use Ublaboo\DataGrid\DataSource\IDataSource;
use Ublaboo\DataGrid\Filter\Filter;
use Ublaboo\DataGrid\Utils\Sorting;
use function call_user_func;
use function count;

abstract class DataSource implements IDataSource
{
    private ?Sorting $sorting = null;

    private ?int $offset = null;

    private ?int $limit = null;

    private ?DoctrineCollectionDataSource $innerDataSource = null;

    /** @var Filter[] */
    private array $filters = [];

    /** @var mixed[] */
    private array $conditions = [];

    /**
     * @return object[]
     */
    abstract protected function loadData() : array;

    public function getCount() : int
    {
        return count($this->getData());
    }

    /**
     * @return mixed[]
     */
    final public function getData() : array
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

    /**
     * @param Filter[] $filters
     */
    final public function filter(array $filters) : self
    {
        foreach ($filters as $filter) {
            if ($filter->hasConditionCallback()) {
                call_user_func($filter->getConditionCallback(), $this, $filter->getValue());
                continue;
            }

            $this->filters[] = $filter;
        }

        return $this;
    }

    /**
     * @param mixed[] $filter
     */
    final public function filterOne(array $filter) : self
    {
        $this->conditions[] = $filter;

        return $this;
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    final public function limit($offset, $limit) : self
    {
        $this->offset = $offset;
        $this->limit  = $limit;

        return $this;
    }

    final public function sort(Sorting $sorting) : self
    {
        $this->sorting = $sorting;

        return $this;
    }

    private function innerDataSource() : IDataSource
    {
        if ($this->innerDataSource === null) {
            $this->innerDataSource = new DoctrineCollectionDataSource(new ArrayCollection($this->loadData()), 'id');
        }

        return $this->innerDataSource;
    }
}
