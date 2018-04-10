<?php

namespace Model;

use Dibi\Row;

class ChitTable extends BaseTable
{

    /**
     * vrací konretní paragon
     * @param int $chitId
     * @return Row|FALSE
     */
    public function get($chitId)
    {
        return $this->connection->fetch("SELECT * FROM [" . self::TABLE_CHIT_VIEW . "] WHERE id=%i", $chitId);
    }

    /**
     * vrací seznam poragonů podle zadaných ID
     * @param int $localEventId
     * @param array $list - seznam id
     * @return array
     */
    public function getIn($localEventId, array $list)
    {
        return $this->connection->fetchAll("SELECT * FROM [" . self::TABLE_CHIT_VIEW . "] WHERE eventId=%i", $localEventId, " AND id in %in", $list, "ORDER BY date");
    }

    /**
     * aktualizuje paragon podle $id
     * @param int $chitId
     * @param array $values
     * @return bool
     */
    public function update($chitId, $values): bool
    {
        return (bool)$this->connection->query("UPDATE [" . self::TABLE_CHIT . "] SET ", $values, "WHERE id=%i", $chitId);
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
