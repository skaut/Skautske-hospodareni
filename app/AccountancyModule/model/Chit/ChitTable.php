<?php
/**
 * @author Hána František
 */

class ChitTable extends BaseTable {
    public function getAll($actionId){
        return dibi::fetchAll("SELECT ch.*, cat.label as clabel, cat.short as cshort, cat.type as ctype FROM [".self::TABLE_CHIT."] as ch
            LEFT JOIN [".self::TABLE_CATEGORY."] as cat ON (ch.category = cat.id) 
                WHERE actionId=%i AND ch.deleted = 0
                ORDER BY ch.date, ctype ", $actionId);
    }
    
    public function add($values){
        return dibi::query("INSERT INTO [".self::TABLE_CHIT."] %v", $values);
    }
    
    public function delete($id, $actionId){
        return dibi::query("UPDATE [".self::TABLE_CHIT."] SET deleted=1 WHERE id = %i AND actionID = %i LIMIT 1", $id, $actionId);
    }
    
    /**
     * vrací seznam kategorií
     * @param type $type
     * @return type 
     */
    public function getCategories($type = NULL){
        return dibi::fetchPairs("SELECT id, label FROM [".self::TABLE_CATEGORY."] WHERE deleted = 0 %if", isset($type), " AND type=%s %end", $type);
    }
    
    /**
     * spočítá příjmy a výdaje a ty pak odečte
     * @param type $actionId
     * @return type 
     */
    public function isInMinus($actionId) {
        $data = dibi::fetchPairs("SELECT cat.type, SUM(ch.price) as sum FROM ac_chits as ch
            LEFT JOIN ac_category as cat ON (ch.category = cat.id) 
            WHERE ch.actionId = %i AND ch.deleted =0
            GROUP BY cat.type", $actionId);
        
        return (($data["in"] - $data["out"]) < 0) ? true : false;
    }
    
}