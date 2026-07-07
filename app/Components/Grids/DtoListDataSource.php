<?php

declare(strict_types=1);

namespace App\Components\Grids;

use App\Utils\CzechStringComparator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\ClosureExpressionVisitor;
use Nette\NotImplementedException;
use Ublaboo\DataGrid\DataSource\IDataSource;
use Ublaboo\DataGrid\Utils\Sorting;

use function usort;

/** @template T */
final class DtoListDataSource implements IDataSource
{
    /** @phpstan-var ArrayCollection<int, T> */
    private ArrayCollection $collection;

    private Criteria $criteria;

    /**
     * @param array<object> $data
     * @phpstan-param array<T> $data
     */
    public function __construct(array $data)
    {
        $this->collection = new ArrayCollection($data);
        $this->criteria = Criteria::create();
    }

    /** @param mixed[] $filters */
    public function filter(array $filters): void
    {
        if ($filters === []) {
            return;
        }

        throw new NotImplementedException('This data source does not support filtering');
    }

    /**
     * @param array<string, mixed> $condition
     *
     * @phpstan-return $this
     */
    public function filterOne(array $condition): self
    {
        if ($condition === []) {
            return $this;
        }

        throw new NotImplementedException('This data source does not support filtering');
    }

    public function getCount(): int
    {
        return $this->getFilteredCollection()->count();
    }

    /**
     * @return object[]
     * @phpstan-return T[]
     */
    public function getData(): array
    {
        return $this->getFilteredCollection()->toArray();
    }

    /** @return $this */
    public function limit(int $offset, int $limit): self
    {
        $this->criteria->setFirstResult($offset)->setMaxResults($limit);

        return $this;
    }

    /** @phpstan-return $this */
    public function sort(Sorting $sorting): self
    {
        $sortCallback = $sorting->getSortCallback();

        if ($sortCallback !== null) {
            return $sortCallback($this, $sorting->getSort());
        }

        $sort = $sorting->getSort();
        $data = $this->collection->toArray();

        usort($data, static function (object $left, object $right) use ($sort): int {
            foreach ($sort as $field => $direction) {
                $result = CzechStringComparator::compareValues(
                    ClosureExpressionVisitor::getObjectFieldValue($left, $field),
                    ClosureExpressionVisitor::getObjectFieldValue($right, $field),
                );

                if ($result !== 0) {
                    return CzechStringComparator::applyDirection($result, $direction);
                }
            }

            return 0;
        });

        $this->collection = new ArrayCollection($data);

        return $this;
    }

    /** @phpstan-return ArrayCollection<int, T> */
    private function getFilteredCollection(): ArrayCollection
    {
        return $this->collection->matching($this->criteria);
    }
}
