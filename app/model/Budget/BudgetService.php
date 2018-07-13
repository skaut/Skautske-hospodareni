<?php

declare(strict_types=1);

namespace Model;

use Model\Cashbook\ObjectType;
use Model\Skautis\Mapper;
use function str_replace;

class BudgetService
{
    /** @var Mapper */
    private $skautisMapper;

    /** @var BudgetTable */
    private $table;

    public function __construct(BudgetTable $table, Mapper $skautisMapper)
    {
        $this->table         = $table;
        $this->skautisMapper = $skautisMapper;
    }

    public function getCategories($oid)
    {
        $localId = $this->getLocalId($oid);
        return [
            'in' => $this->getCategoriesAll($localId, 'in'),
            'out' => $this->getCategoriesAll($localId, 'out'),
        ];
    }

    public function addCategory(int $oid, string $label, string $type, ?int $parentId, string $value, int $year) : void
    {
        $this->table->addCategory([
            'objectId' => $this->getLocalId($oid),
            'label' => $label,
            'type' => $type,
            'parentId' => $parentId,
            'value' => (float) str_replace(',', '.', $value),
            'year' => $year,
        ]);
    }

    /**
     * @return string[]
     */
    public function getCategoriesRoot(int $oid, ?string $type = null) : array
    {
        $localId = $this->getLocalId($oid);

        if ($type === null) {
            return [
                'in' => $this->table->getDS($localId, 'in')->where('parentId IS NULL')->fetchPairs('id', 'label'),
                'out' => $this->table->getDS($localId, 'out')->where('parentId IS NULL')->fetchPairs('id', 'label'),
            ];
        }
        return $this->table->getDS($localId, $type)->where('parentId IS NULL')->fetchPairs('id', 'label');
    }

    /**
     * @return string[]
     */
    public function getCategoriesAll(int $oid, string $type, ?int $parentId = null) : array
    {
        $data = $this->table->getCategoriesByParent($oid, $type, $parentId);
        foreach ($data as $k => $v) {
            $data[$k]['childrens'] = $this->{__FUNCTION__}($oid, $type, $v->id);
        }
        return $data;
    }

    /**
     * @return string[]
     */
    public function getCategoriesLeaf(int $oid, ?string $type = null) : array
    {
        if ($type === null) {
            return [
                'in' => $this->{__FUNCTION__}($oid, 'in'),
                'out' => $this->{__FUNCTION__}($oid, 'out'),
            ];
        }
        return $this->table->getDS($this->getLocalId($oid), $type)->where('parentId IS NOT NULL')->fetchPairs('id', 'label');
    }

    private function getLocalId(int $id) : int
    {
        return $this->skautisMapper->getLocalId($id, ObjectType::UNIT)->toInt();
    }
}
