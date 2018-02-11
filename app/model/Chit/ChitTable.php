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
     * vrací seznam kategorií obecné akce
     * @return array
     */
    public function getCategoriesPairsByType(string $type, ?string $inout = NULL)
    {
        return $this->connection->fetchPairs("SELECT id, label FROM [" . self::TABLE_CATEGORY . "] c LEFT JOIN [" . self::TABLE_CATEGORY_OBJECT . "] cc ON cc.categoryId = c.id WHERE deleted = 0 and cc.objectTypeId = %s ",$type," %if", isset($inout), " AND type=%s %end", $inout, "ORDER BY orderby DESC");
    }

    /**
     * vrací všechny informace o kategoriích
     * @return array
     */
    public function getGeneralCategories(?string $type = NULL)
    {
        return $this->connection->fetchAll("SELECT * FROM [" . self::TABLE_CATEGORY . "] WHERE deleted = 0 %if", isset($type), " AND type=%s %end", $type);
    }

    /**
     * celková cena v dané kategorii
     */
    public function getTotalInCategory(int $categoryId, int $eId): int
    {
        return (int)$this->connection->fetchSingle("SELECT SUM(price) FROM [" . self::TABLE_CHIT . "] WHERE category = %i", $categoryId, " AND deleted=0 AND eventId=%i", $eId, " GROUP BY eventId");
    }

    /**
     * spočítá příjmy a výdaje a ty pak odečte
     *
     * @TODO refactor to Cashbook aggregate
     */
    public function eventIsInMinus(int $localEventId): bool
    {
        $finalBalance = $this->connection->fetchSingle("SELECT SUM(IF(cat.type = 'in', ch.price, ch.price * -1)) FROM [" . self::TABLE_CHIT . "] as ch
            LEFT JOIN [" . self::TABLE_CATEGORY . "] as cat ON (ch.category = cat.id) 
            WHERE ch.eventId = %i AND ch.deleted = 0", $localEventId);

        return $finalBalance < 0;
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
