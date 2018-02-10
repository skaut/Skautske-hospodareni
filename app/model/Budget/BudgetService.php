<?php

namespace Model;

use Model\Cashbook\ObjectType;
use Model\Skautis\Mapper;
use Skautis\Skautis;

class BudgetService extends BaseService
{

    /** @var Mapper */
    private $skautisMapper;

    /** @var BudgetTable */
    private $table;

    public function __construct(BudgetTable $table, Skautis $skautis, Mapper $skautisMapper)
    {
        $this->table = $table;
        $this->skautisMapper = $skautisMapper;
        parent::__construct($skautis);
    }

    public function getCategories($oid)
    {
        $localId = $this->getLocalId($oid);
        return [
            "in" => $this->getCategoriesAll($localId, "in"),
            "out" => $this->getCategoriesAll($localId, "out")
        ];
    }

    public function addCategory(int $oid, $label, $type, $parentId, $value, $year): void
    {
        $this->table->addCategory([
            "objectId" => $this->getLocalId($oid),
            "label" => $label,
            "type" => $type,
            "parentId" => $parentId,
            "value" => (float)str_replace(",", ".", $value),
            "year" => $year,
        ]);
    }

    public function getCategoriesRoot(int $oid, $type = NULL)
    {
        $localId = $this->getLocalId($oid);

        if (is_null($type)) {
            return [
                'in' => $this->table->getDS($localId, 'in')->where("parentId IS NULL")->fetchPairs("id", "label"),
                'out' => $this->table->getDS($localId, 'out')->where("parentId IS NULL")->fetchPairs("id", "label")
            ];
        }
        return $this->table->getDS($localId, $type)->where("parentId IS NULL")->fetchPairs("id", "label");
    }

    public function getCategoriesLeaf(int $oid, $type = NULL)
    {
        if (is_null($type)) {
            return [
                'in' => $this->{__FUNCTION__}($oid, 'in'),
                'out' => $this->{__FUNCTION__}($oid, 'out'),
            ];
        }
        return $this->table->getDS($this->getLocalId($oid), $type)->where("parentId IS NOT NULL")->fetchPairs("id", "label");
    }

    public function getCategoriesAll($oid, $type, $parentId = NULL)
    {
        $data = $this->table->getCategoriesByParent($oid, $type, $parentId);
        foreach ($data as $k => $v) {
            $data[$k]['childrens'] = $this->{__FUNCTION__}($oid, $type, $v->id);
        }
        return $data;
    }

    private function getLocalId(int $id) : int
    {
        return $this->skautisMapper->getLocalId($id, ObjectType::UNIT);
    }

}
