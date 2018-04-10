<?php

namespace Model;

use eGen\MessageBus\Bus\CommandBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Skautis\Mapper;
use Skautis\Skautis;

class ChitService extends MutableBaseService
{

    /** @var Mapper */
    private $skautisMapper;

    /** @var ChitTable */
    private $table;

    public function __construct(
        string $name,
        ChitTable $table,
        Skautis $skautIS,
        Mapper $skautisMapper,
        CommandBus $commandBus
    )
    {
        parent::__construct($name, $skautIS);
        $this->table = $table;
        $this->skautisMapper = $skautisMapper;
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

    public function getLocalId(int $skautisEventId, string $type = NULL): CashbookId
    {
        return $this->skautisMapper->getLocalId($skautisEventId, $type ?? $this->type);
    }

    public function getCashbookIdFromSkautisId(int $skautisid): CashbookId
    {
        return $this->getLocalId($skautisid);
    }

}
