<?php

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
        return dibi::fetch("SELECT * FROM [" . self::TABLE_CHIT_VIEW . "] WHERE id=%i", $chitId);
    }

    /**
     * vrací seznam všech paragonů k danému $actionId
     * @param int $actionId
     * @return array
     */
    public function getAll($actionId) {
        return dibi::fetchAll("SELECT * FROM [" . self::TABLE_CHIT_VIEW . "]
                WHERE actionId=%i", $actionId, "
                ORDER BY date, ctype ");
    }

    /**
     * vrací seznam poragonů podle zadaných ID
     * @param int $actionId
     * @param array $list - seznam id
     * @return array
     */
    public function getIn($actionId, array $list) {
        return dibi::fetchAll("SELECT * FROM [" . self::TABLE_CHIT_VIEW . "] WHERE actionId=%i", $actionId, " AND id in %in", $list);
    }

    /**
     * přidá paragon do tabulky
     * @param array $values
     * @return int 
     */
    public function add($values) {
        dibi::query("INSERT INTO [" . self::TABLE_CHIT . "] %v", $values);
        return dibi::getInsertId();
    }

    /**
     * aktualizuje paragon podle $id
     * @param int $chitId
     * @param array $values
     * @return type 
     */
    public function update($chitId, $values) {
        return dibi::query("UPDATE [" . self::TABLE_CHIT . "] SET ", $values, "WHERE id=%s", $chitId);
    }

    /**
     * označí paragon jako smazaný
     * @param int $chitId
     * @param int $actionId
     * @return type 
     */
    public function delete($chitId, $actionId) {
        return dibi::query("UPDATE [" . self::TABLE_CHIT . "] SET deleted=1 WHERE id = %i AND actionID = %i LIMIT 1", $chitId, $actionId);
    }

    /**
     * označí paragony z dané akce za smazané
     * @param type $actionId
     * @return type 
     */
    public function deleteAll($actionId) {
        return dibi::query("UPDATE [" . self::TABLE_CHIT . "] SET deleted=1 WHERE actionID = %i", $actionId);
    }

    /**
     * vrací seznam kategorií
     * @param string $type
     * @return array 
     */
    public function getCategories($type = NULL) {
        return dibi::fetchPairs("SELECT id, label FROM [" . self::TABLE_CATEGORY . "]
            WHERE deleted = 0 %if", isset($type), " AND type=%s %end", $type, "ORDER BY orderby DESC"
        );
    }

    /**
     * vrací všechny informace o kategoriích
     * @param string $type in|out
     * @return type 
     */
    public function getCategoriesAll($type = NULL) {
        return dibi::fetchAll("SELECT * FROM [" . self::TABLE_CATEGORY . "] WHERE deleted = 0 %if", isset($type), " AND type=%s %end", $type);
    }
    
    /**
     * celková cena v dané kategorii
     * @param int $categoryId
     * @param string $type - camp/general
     * @return int 
     */
    public function getTotalInCategory($categoryId, $type="camp"){
        return dibi::fetchSingle("SELECT SUM(price) FROM [" . self::TABLE_CHIT . "] WHERE category = %i", $categoryId, " AND deleted=0 AND type=%s", $type);
    }

    /**
     * spočítá příjmy a výdaje a ty pak odečte
     * @param int $actionId
     * @return bool 
     */
    public function isInMinus($actionId) {
        $data = dibi::fetchPairs("SELECT cat.type, SUM(ch.price) as sum FROM [" . self::TABLE_CHIT . "] as ch
            LEFT JOIN [" . self::TABLE_CATEGORY . "] as cat ON (ch.category = cat.id) 
            WHERE ch.actionId = %i AND ch.deleted = 0
            GROUP BY cat.type", $actionId);
        return $data;
    }
    
    /**
     * spočte součet částek v jednotlivých kategoriích
     * @param type $actionId
     * @return (categoryId=>SUM)
     */
    public function getTotalInCategories($actionId) {
        return dibi::fetchPairs("SELECT category, SUM(price) FROM [" . self::TABLE_CHIT . "] WHERE actionId=%i", $actionId ," AND deleted=0 GROUP BY category");
    }

}