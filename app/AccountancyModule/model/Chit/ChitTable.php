<?php
/**
 * @author Hána František
 */

class ChitTable extends BaseTable {
    
    /**
     * vrací konretní paragon
     * @param type $id
     * @return type 
     */
    public function get($id){
        return dibi::fetch("SELECT ch.*, cat.type as ctype FROM [".self::TABLE_CHIT."] as ch
            LEFT JOIN [".self::TABLE_CATEGORY."] as cat ON (ch.category = cat.id) 
                WHERE ch.id=%i AND ch.deleted = 0", $id);
    }
    
    /**
     * vrací seznam všech paragonů k danému $actionId
     * @param type $actionId
     * @return type 
     */
    public function getAll($actionId){
        return dibi::fetchAll("SELECT ch.*, cat.label as clabel, cat.short as cshort, cat.type as ctype FROM [".self::TABLE_CHIT."] as ch
            LEFT JOIN [".self::TABLE_CATEGORY."] as cat ON (ch.category = cat.short) 
                WHERE actionId=%i AND ch.deleted = 0
                ORDER BY ch.date, ctype ", $actionId);
    }
    
    /**
     * přidá paragon do tabulky
     * @param array $values
     * @return int 
     */
    public function add($values){
        return dibi::query("INSERT INTO [".self::TABLE_CHIT."] %v", $values);
    }
    
    public function update($id, $values){
        return dibi::query("UPDATE [".self::TABLE_CHIT."] SET ", $values, "WHERE id=%s", $id);
    }
    
    /**
     * označí paragon jako smazaný
     * @param type $id
     * @param type $actionId
     * @return type 
     */
    public function delete($id, $actionId){
        return dibi::query("UPDATE [".self::TABLE_CHIT."] SET deleted=1 WHERE id = %i AND actionID = %i LIMIT 1", $id, $actionId);
    }
    
    /**
     * vrací seznam kategorií
     * @param string $type
     * @return array 
     */
    public function getCategories($type = NULL){
        return dibi::fetchPairs("SELECT short, label FROM [".self::TABLE_CATEGORY."] WHERE deleted = 0 %if", isset($type), " AND type=%s %end", $type);
    }
    
    /**
     * vrací všechny informace o kategoriích
     * @param string $type in|out
     * @return type 
     */
    public function getCategoriesAll($type = NULL){
        return dibi::fetchAll("SELECT * FROM [".self::TABLE_CATEGORY."] WHERE deleted = 0 %if", isset($type), " AND type=%s %end", $type);
    }
    
    /**
     * spočítá příjmy a výdaje a ty pak odečte
     * @param int $actionId
     * @return bool 
     */
    public function isInMinus($actionId) {
        $data = dibi::fetchPairs("SELECT cat.type, SUM(ch.price) as sum FROM ac_chits as ch
            LEFT JOIN ac_category as cat ON (ch.category = cat.short) 
            WHERE ch.actionId = %i AND ch.deleted = 0
            GROUP BY cat.type", $actionId);
        
        return @(($data["in"] - $data["out"]) < 0) ? true : false; //@ potlačuje chyby u neexistujicich indexů "in" a "out"
    }
    
}