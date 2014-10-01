<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BudgetTable extends BaseTable {

    public function getCategories($type = NULL) {
        return $this->connection->fetchPairs("SELECT id, label FROM [" . self::TABLE_UNIT_BUDGET_CATEGORY . "]
            WHERE deleted = 0 %if", isset($type), " AND type=%s %end", $type);
    }

    public function getDS($unitId, $type) {
        return $this->connection->dataSource("SELECT * FROM [" . self::TABLE_UNIT_BUDGET_CATEGORY . "] WHERE "
                        . "deleted = 0 AND "
                        . "type = %s ", $type, "AND "
                        . "objectId = %i ", $unitId);
    }

    public function getCategoriesByParent($unitId, $type, $parentId) {
        return $this->connection->query("SELECT * FROM [" . self::TABLE_UNIT_BUDGET_CATEGORY . "] WHERE "
                                . "deleted = 0 AND "
                                . "type = %s ", $type, "AND "
                                . "parentId %if ", is_null($parentId), " IS %else = %end %i", $parentId, " AND "
                                . "objectId = %i ", $unitId)
                        ->fetchAssoc("id");
    }

    public function addCategory($arr) {
        $this->connection->query("INSERT INTO [" . self::TABLE_UNIT_BUDGET_CATEGORY . "] %v", $arr);
        return TRUE;
    }

}
