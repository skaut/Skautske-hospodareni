<?php

namespace Model;

use Dibi\Row;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class ChitTable extends BaseTable
{

    /**
     * vrací konretní paragon
     * @param int $chitId
     * @return Row
     */
    public function get($chitId)
    {
        return $this->connection->fetch("SELECT * FROM [" . self::TABLE_CHIT_VIEW . "] WHERE id=%i", $chitId);
    }

    /**
     * vrací seznam všech paragonů k danému $actionId
     * @param int $localEventId
     * @return array
     */
    public function getAll($localEventId, $onlyUnlocked)
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
     * přidá paragon do tabulky
     * @param array $values
     * @return int
     */
    public function add($values)
    {
        $this->connection->query("INSERT INTO [" . self::TABLE_CHIT . "] %v", $values);
        return $this->connection->getInsertId();
    }

    /**
     * generuje pořadové číslo dokladu
     * @param int $eventId
     * @param array(id_kategorií) $category
     * @param int $length - délka čísla
     * @return string
     */
    public function generateNumber($eventId, $category, $length = 3)
    {
        return str_pad((int)$this->connection->fetchSingle("SELECT COUNT(*) from ac_chits where eventId=%i and category IN %in", $eventId, $category), $length, "0", STR_PAD_LEFT);
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
     * označí paragon jako smazaný
     * @param int $chitId
     * @param int $localEventId
     * @return bool
     */
    public function delete($chitId, $localEventId)
    {
        return (bool)$this->connection->query("UPDATE [" . self::TABLE_CHIT . "] SET deleted=1 WHERE id = %i AND eventId = %i LIMIT 1", $chitId, $localEventId);
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
     * vrací seznam kategorií
     * @param string $type
     * @return array
     */
    public function getGeneralCategoriesPairs($type = NULL)
    {
        return $this->connection->fetchPairs("SELECT id, label FROM [" . self::TABLE_CATEGORY . "] WHERE deleted = 0 %if", isset($type), " AND type=%s %end", $type, "ORDER BY orderby DESC"
        );
    }

    /**
     * vrací všechny informace o kategoriích
     * @param string $type in|out
     * @return array
     */
    public function getGeneralCategories($type = NULL)
    {
        return $this->connection->fetchAll("SELECT * FROM [" . self::TABLE_CATEGORY . "] WHERE deleted = 0 %if", isset($type), " AND type=%s %end", $type);
    }

    /**
     * celková cena v dané kategorii
     * @param int $categoryId
     * @param string $eId - camp/general
     * @return int
     */
    public function getTotalInCategory($categoryId, $eId)
    {
        return $this->connection->fetchSingle("SELECT SUM(price) FROM [" . self::TABLE_CHIT . "] WHERE category = %i", $categoryId, " AND deleted=0 AND eventId=%i", $eId, " GROUP BY eventId");
    }

    /**
     * spočítá příjmy a výdaje a ty pak odečte
     * @param int $localEventId
     * @return bool
     */
    public function eventIsInMinus($localEventId)
    {
        $data = $this->connection->fetchAll("SELECT cat.type, SUM(ch.price) as sum FROM [" . self::TABLE_CHIT . "] as ch
            LEFT JOIN [" . self::TABLE_CATEGORY . "] as cat ON (ch.category = cat.id) 
            WHERE ch.eventId = %i AND ch.deleted = 0
            GROUP BY cat.type", $localEventId);
        return (bool)$data;
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
     * @param int $oid
     * @param int $chitId
     * @param int $userId
     */
    public function lock($oid, $chitId, $userId): void
    {
        $this->connection->query("UPDATE [" . self::TABLE_CHIT . "] SET `lock`=%i ", $userId, " WHERE eventId=%i AND id=%i", $oid, $chitId);
    }

    public function unlock($oid, $chitId)
    {
        return $this->connection->query("UPDATE [" . self::TABLE_CHIT . "] SET `lock`=NULL WHERE eventId=%i AND id=%i", $oid, $chitId);
    }

    public function lockEvent($oid, $userId)
    {
        return $this->connection->query("UPDATE [" . self::TABLE_CHIT . "] SET `lock`=%i ", $userId, " WHERE eventId=%i ", $oid, "AND `lock` IS NULL");
    }

    /**
     * @param int[] $categories
     * @param string $type
     * @return array
     */
    public function getBudgetCategoriesSummary(array $categories, string $type)
    {
        $catName = "budgetCategory" . ucfirst($type);
        return $this->connection->fetchPairs("SELECT $catName, SUM(price) FROM [" . self::TABLE_CHIT . "] WHERE $catName IN %in", $categories, " AND deleted=0 GROUP BY $catName");
    }

}
