<?php

namespace Model;

class ChitService
{

    /** @var ChitTable */
    private $table;

    public function __construct(ChitTable $table)
    {
        $this->table = $table;
    }

    /**
     * nastavuje kategorie z rozpočtu
     * @param int $chitId
     * @param int|NULL $in
     * @param int|NULL $out
     */
    public function setBudgetCategories($chitId, $in = NULL, $out = NULL): void
    {
        $this->table->update($chitId, ["budgetCategoryIn" => $in, "budgetCategoryOut" => $out]);
    }

    /**
     * @param array $categories
     * @return array
     */
    public function getBudgetCategoriesSummary($categories)
    {
        return $this->table->getBudgetCategoriesSummary(array_keys($categories['in']), 'in') + $this->table->getBudgetCategoriesSummary(array_keys($categories['out']), 'out');
    }

}
