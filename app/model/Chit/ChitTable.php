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
     * vrací seznam všech paragonů k danému $actionId
     * @param int $localEventId
     * @return Row[]
     */
    public function getAll($localEventId, $onlyUnlocked): array
    {
        return $this->connection->query("SELECT * FROM [" . self::TABLE_CHIT_VIEW . "]
                WHERE eventId=%i", $localEventId, " AND deleted=0
                    %if ", $onlyUnlocked, " AND [lock] IS NULL %end
                ORDER BY date, ctype, num, cshort")->fetchAssoc("id");
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
     * označí paragony z dané akce za smazané
     * @param int $localEventId
     */
    public function deleteAll($localEventId): void
    {
        $this->connection->query("UPDATE [" . self::TABLE_CHIT . "] SET deleted=1 WHERE eventId = %i", $localEventId);
    }

    /**
     * spočte součet částek v jednotlivých kategoriích
     * @param int $localEventId
     * @return array (categoryId=>SUM)
     */
    public function getTotalInCategories($localEventId): array
    {
        return $this->connection->fetchPairs("SELECT category, SUM(price) FROM [" . self::TABLE_CHIT . "] WHERE eventId=%i", $localEventId, " AND deleted=0 GROUP BY category");
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
