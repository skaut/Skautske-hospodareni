<?php

namespace Model;

class ChitTable extends BaseTable
{

    /**
     * aktualizuje paragon podle $id
     * @param int $chitId
     * @param array $values
     */
    public function update($chitId, $values): void
    {
        $this->connection->query("UPDATE [" . self::TABLE_CHIT . "] SET ", $values, "WHERE id=%i", $chitId);
    }

    /**
     * @param int[] $categories
     * @return array
     */
    public function getBudgetCategoriesSummary(array $categories, string $type)
    {
        $catName = "budgetCategory" . ucfirst($type);
        return $this->connection->fetchPairs("SELECT $catName, SUM(price) FROM [" . self::TABLE_CHIT . "] WHERE $catName IN %in", $categories, " AND deleted=0 GROUP BY $catName");
    }

}
