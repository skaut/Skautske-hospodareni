<?php

namespace Model;

/**
 * @author Hána František
 */
class ChitTable extends BaseTable {

    /**
     * vrací konretní paragon
     * @param type $chitId
     * @return DibiRow 
     */
    public function get($chitId) {
        return $this->connection->fetch("SELECT * FROM [" . self::TABLE_CHIT_VIEW . "] WHERE id=%i", $chitId);
    }

    /**
     * vrací seznam všech paragonů k danému $actionId
     * @param int $localEventId
     * @return array
     */
    public function getAll($localEventId) {
        return $this->connection->fetchAll("SELECT * FROM [" . self::TABLE_CHIT_VIEW . "]
                WHERE eventId=%i", $localEventId, "
                ORDER BY date, ctype ");
    }

    /**
     * vrací seznam poragonů podle zadaných ID
     * @param int $localEventId
     * @param array $list - seznam id
     * @return array
     */
    public function getIn($localEventId, array $list) {
        return $this->connection->fetchAll("SELECT * FROM [" . self::TABLE_CHIT_VIEW . "] WHERE eventId=%i", $localEventId, " AND id in %in", $list, "ORDER BY date");
    }

    /**
     * přidá paragon do tabulky
     * @param array $values
     * @return int 
     */
    public function add($values) {
        $this->connection->query("INSERT INTO [" . self::TABLE_CHIT . "] %v", $values);
        return $this->connection->getInsertId();
    }
    
    /**
     * generuje pořadové číslo dokladu
     * @param int $eventId
     * @param array(id_kategorií) $category
     * @param itn $length - délka čísla
     * @return type
     */
    public function generateNumber($eventId, $category, $length = 3){
        return str_pad((int) $this->connection->fetchSingle("SELECT COUNT(*) from ac_chits where eventId=%i and category IN %in", $eventId, $category),$length,"0",STR_PAD_LEFT);
    }

    /**
     * aktualizuje paragon podle $id
     * @param int $chitId
     * @param array $values
     * @return type 
     */
    public function update($chitId, $values) {
        return $this->connection->query("UPDATE [" . self::TABLE_CHIT . "] SET ", $values, "WHERE id=%i", $chitId);
    }

    /**
     * označí paragon jako smazaný
     * @param int $chitId
     * @param int $localEventId
     * @return type 
     */
    public function delete($chitId, $localEventId) {
        return $this->connection->query("UPDATE [" . self::TABLE_CHIT . "] SET deleted=1 WHERE id = %i AND eventId = %i LIMIT 1", $chitId, $localEventId);
    }

    /**
     * označí paragony z dané akce za smazané
     * @param type $localEventId
     * @return type 
     */
    public function deleteAll($localEventId) {
        return $this->connection->query("UPDATE [" . self::TABLE_CHIT . "] SET deleted=1 WHERE eventId = %i", $localEventId);
    }

    /**
     * vrací seznam kategorií
     * @param string $type
     * @return array 
     */
    public function getCategories($type = NULL) {
        return $this->connection->fetchPairs("SELECT id, label FROM [" . self::TABLE_CATEGORY . "]
            WHERE deleted = 0 %if", isset($type), " AND type=%s %end", $type, "ORDER BY orderby DESC"
        );
    }

    /**
     * vrací všechny informace o kategoriích
     * @param string $type in|out
     * @return type 
     */
    public function getCategoriesAll($type = NULL) {
        return $this->connection->fetchAll("SELECT * FROM [" . self::TABLE_CATEGORY . "] WHERE deleted = 0 %if", isset($type), " AND type=%s %end", $type);
    }

    /**
     * celková cena v dané kategorii
     * @param int $categoryId
     * @param string $type - camp/general
     * @return int 
     */
    public function getTotalInCategory($categoryId, $eId) {
        return $this->connection->fetchSingle("SELECT SUM(price) FROM [" . self::TABLE_CHIT . "] WHERE category = %i", $categoryId, " AND deleted=0 AND eventId=%i", $eId, " GROUP BY eventId");
    }

    /**
     * spočítá příjmy a výdaje a ty pak odečte
     * @param int $localEventId
     * @return bool 
     */
    public function isInMinus($localEventId) {
        $data = $this->connection->fetchAll("SELECT cat.type, SUM(ch.price) as sum FROM [" . self::TABLE_CHIT . "] as ch
            LEFT JOIN [" . self::TABLE_CATEGORY . "] as cat ON (ch.category = cat.id) 
            WHERE ch.eventId = %i AND ch.deleted = 0
            GROUP BY cat.type", $localEventId);
        return $data;
    }

    /**
     * spočte součet částek v jednotlivých kategoriích
     * @param type $localEventId
     * @return (categoryId=>SUM)
     */
    public function getTotalInCategories($localEventId) {
        return $this->connection->fetchPairs("SELECT category, SUM(price) FROM [" . self::TABLE_CHIT . "] WHERE eventId=%i", $localEventId, " AND deleted=0 GROUP BY category");
    }

}