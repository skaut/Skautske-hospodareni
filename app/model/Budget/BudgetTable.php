<?php

declare(strict_types=1);

namespace Model;

use Dibi\DataSource;

class BudgetTable extends BaseTable
{
    public function getDS(int $unitId, string $type) : DataSource
    {
        return $this->connection->dataSource('SELECT * FROM [' . self::TABLE_UNIT_BUDGET_CATEGORY . '] WHERE '
            . 'deleted = 0 AND '
            . 'type = %s ', $type, 'AND '
            . 'objectId = %i ', $unitId);
    }

    /**
     * @return string[]
     */
    public function getCategoriesByParent(int $unitId, string $type, ?int $parentId) : array
    {
        $categories = $this->connection->fetchAll('SELECT * FROM [' . self::TABLE_UNIT_BUDGET_CATEGORY . '] WHERE '
            . 'deleted = 0 AND '
            . 'type = %s ', $type, 'AND '
            . 'parentId %if ', $parentId === null, ' IS %else = %end %i', $parentId, ' AND '
            . 'objectId = %i ', $unitId);
        $result     = [];

        foreach ($categories as $category) {
            $result[$category->id] = $category;
        }

        return $result;
    }

    /**
     * @param mixed[] $arr
     */
    public function addCategory(array $arr) : bool
    {
        $this->connection->query('INSERT INTO [' . self::TABLE_UNIT_BUDGET_CATEGORY . '] %v', $arr);
        return true;
    }
}
