<?php

declare(strict_types=1);

namespace App\AccountancyModule\Grids;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Nette\NotImplementedException;
use Ublaboo\DataGrid\DataSource\IDataSource;
use Ublaboo\DataGrid\Utils\Sorting;

/**
 * @template T
 */
final class DtoListDataSource implements IDataSource
{
    /** @phpstan-var ArrayCollection<int, T> */
    private ArrayCollection $collection;

    private Criteria $criteria;

    /**
     * @param array<object> $data
     *
     * @phpstan-param array<T> $data
     */
    public function __construct(array $data)
    {
        $this->collection = new ArrayCollection($data);
        $this->criteria   = Criteria::create();
    }

    /**
     * @param mixed[] $filters
     *
     * @phpstan-return $this
     */
    public function filter(array $filters) : self
    {
        if ($filters === []) {
            return $this;
        }

        throw new NotImplementedException('This data source does not support filtering');
    }

    /**
     * @param array<string, mixed> $condition
     *
     * @phpstan-return $this
     */
    public function filterOne(array $condition) : self
    {
        if ($condition === []) {
            return $this;
        }

        throw new NotImplementedException('This data source does not support filtering');
    }

    public function getCount() : int
    {
        return $this->getFilteredCollection()->count();
    }

    /**
     * @return object[]
     *
     * @phpstan-return T[]
     */
    public function getData() : array
    {
        return $this->getFilteredCollection()->toArray();
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return $this
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function limit($offset, $limit) : self
    {
        $this->criteria->setFirstResult($offset)->setMaxResults($limit);

        return $this;
    }

    /**
     * @phpstan-return $this
     */
    public function sort(Sorting $sorting) : self
    {
        $sortCallback = $sorting->getSortCallback();

        if ($sortCallback !== null) {
            return $sortCallback($this, $sorting->getSort());
        }

        $this->criteria->orderBy($sorting->getSort());

        return $this;
    }

    /**
     * @phpstan-return ArrayCollection<int, T>
     */
    private function getFilteredCollection() : ArrayCollection
    {
        return $this->collection->matching($this->criteria);
    }
}
