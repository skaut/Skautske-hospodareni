<?php

namespace Model;

use Dibi\Connection;
use Model\Skautis\Mapper;
use Skautis\Skautis;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BudgetService extends BaseService {

    /**
     * @var Mapper
     */
    private $skautisMapper;

    /** @var BudgetTable */
    private $table;

    public function __construct(BudgetTable $table, Skautis $skautis, Connection $connection, Mapper $skautisMapper)
    {
        $this->table = $table;
        $this->skautisMapper = $skautisMapper;
        parent::__construct($skautis, $connection);
    }

    public function getCategories($oid) {
        return array(
            "in" => $this->getCategoriesAll($this->getLocalId($oid, self::TYPE_UNIT), "in"),
            "out" => $this->getCategoriesAll($this->getLocalId($oid, self::TYPE_UNIT), "out"));
    }

    public function addCategory($oid, $label, $type, $parentId, $value, $year) {
        $this->table->addCategory(array(
            "objectId" => $this->getLocalId($oid, self::TYPE_UNIT),
            "label" => $label,
            "type" => $type,
            "parentId" => $parentId,
            "value" => (float) str_replace(",", ".", $value),
            "year" => $year,
        ));
    }

    public function getCategoriesRoot($oid, $type = NULL) {
        if (is_null($type)) {
            return array(
                'in' => $this->table->getDS($this->getLocalId($oid, self::TYPE_UNIT), 'in')->where("parentId IS NULL")->fetchPairs("id", "label"),
                'out' => $this->table->getDS($this->getLocalId($oid, self::TYPE_UNIT), 'out')->where("parentId IS NULL")->fetchPairs("id", "label")
            );
        }
        return $this->table->getDS($this->getLocalId($oid, self::TYPE_UNIT), $type)->where("parentId IS NULL")->fetchPairs("id", "label");
    }

    public function getCategoriesLeaf($oid, $type = NULL) {
        if (is_null($type)) {
            return array(
                'in' => $this->{__FUNCTION__}($oid, 'in'),
                'out' => $this->{__FUNCTION__}($oid, 'out'),
            );
        }
        return $this->table->getDS($this->getLocalId($oid, self::TYPE_UNIT), $type)->where("parentId IS NOT NULL")->fetchPairs("id", "label");
    }

    public function getCategoriesAll($oid, $type, $parentId = NULL) {
        $data = $this->table->getCategoriesByParent($oid, $type, $parentId);
        foreach ($data as $k => $v) {
            $data[$k]['childrens'] = $this->{__FUNCTION__}($oid, $type, $v->id);
        }
        return $data;
    }

}
