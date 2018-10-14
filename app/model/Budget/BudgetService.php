<?php

declare(strict_types=1);

namespace Model;

use function str_replace;

class BudgetService
{
    /** @var BudgetTable */
    private $table;

    public function __construct(BudgetTable $table)
    {
        $this->table = $table;
    }

    /**
     * @return mixed[]
     */
    public function getCategories(int $unitId) : array
    {
        return [
            'in' => $this->getCategoriesAll($unitId, 'in'),
            'out' => $this->getCategoriesAll($unitId, 'out'),
        ];
    }

    public function addCategory(int $unitId, string $label, string $type, ?int $parentId, string $value, int $year) : void
    {
        $this->table->addCategory([
            'unit_id' => $unitId,
            'label' => $label,
            'type' => $type,
            'parentId' => $parentId,
            'value' => (float) str_replace(',', '.', $value),
            'year' => $year,
        ]);
    }

    /**
     * @return mixed[]
     */
    public function getCategoriesRoot(int $unitId, ?string $type = null) : array
    {
        if ($type === null) {
            return [
                'in' => $this->table->getDS($unitId, 'in')->where('parentId IS NULL')->fetchPairs('id', 'label'),
                'out' => $this->table->getDS($unitId, 'out')->where('parentId IS NULL')->fetchPairs('id', 'label'),
            ];
        }
        return $this->table->getDS($unitId, $type)->where('parentId IS NULL')->fetchPairs('id', 'label');
    }

    /**
     * @return mixed[]
     */
    public function getCategoriesAll(int $unitId, string $type, ?int $parentId = null) : array
    {
        $data = $this->table->getCategoriesByParent($unitId, $type, $parentId);
        foreach ($data as $k => $v) {
            $data[$k]['childrens'] = $this->{__FUNCTION__}($unitId, $type, $v['id']);
        }
        return $data;
    }

    /**
     * @return string[]
     */
    public function getCategoriesLeaf(int $unitId, ?string $type = null) : array
    {
        if ($type === null) {
            return [
                'in' => $this->{__FUNCTION__}($unitId, 'in'),
                'out' => $this->{__FUNCTION__}($unitId, 'out'),
            ];
        }
        return $this->table->getDS($unitId, $type)->where('parentId IS NOT NULL')->fetchPairs('id', 'label');
    }
}
