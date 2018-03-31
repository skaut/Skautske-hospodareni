<?php

namespace Model;

use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Commands\Cashbook\UpdateCampCategoryTotal;
use Model\Cashbook\ReadModel\Queries\CategoryPairsQuery;
use Model\Skautis\Mapper;
use Skautis\Skautis;

class ChitService extends MutableBaseService
{

    const CHIT_UNDEFINED_OUT = 8;
    const CHIT_UNDEFINED_IN = 12;
    const SKAUTIS_BUDGET_RESERVE = 15;

    /** @var Mapper */
    private $skautisMapper;

    /** @var ChitTable */
    private $table;

    /** @var CommandBus */
    private $commandBus;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(
        string $name,
        ChitTable $table,
        Skautis $skautIS,
        Mapper $skautisMapper,
        CommandBus $commandBus,
        QueryBus $queryBus
    )
    {
        parent::__construct($name, $skautIS);
        $this->table = $table;
        $this->skautisMapper = $skautisMapper;
        $this->commandBus = $commandBus;
        $this->queryBus = $queryBus;
    }

    /**
     * smazat všechny paragony dané akce
     * použití při rušení celé akce
     */
    public function deleteAll(int $skautisEventId): void
    {
        $this->table->deleteAll($this->getLocalId($skautisEventId)->toInt());
    }

    /**
     * seznam všech kategorií pro daný typ
     * @param bool $isEstimate - předpoklad?
     * @return array
     */
    public function getCategories(int $skautisEventId, bool $isEstimate = FALSE): array
    {
        if ($this->type == self::TYPE_CAMP) {
            //přidání kategorií k táborům
            $res = [//8 a 12 jsou ID použitá i u výprav
                self::CHIT_UNDEFINED_OUT => (object)["ID" => self::CHIT_UNDEFINED_OUT, "IsRevenue" => FALSE, "EventCampStatementType" => "Neurčeno", "Ammount" => 0],
                self::CHIT_UNDEFINED_IN => (object)["ID" => self::CHIT_UNDEFINED_IN, "IsRevenue" => TRUE, "EventCampStatementType" => "Neurčeno", "Ammount" => 0],
            ];
            foreach ($this->skautis->event->EventCampStatementAll(["ID_EventCamp" => $skautisEventId, "IsEstimate" => $isEstimate]) as $i) { //prepisuje na tvar s klíčem jako ID
                if ($isEstimate == FALSE && $i->ID_EventCampStatementType == self::SKAUTIS_BUDGET_RESERVE) {
                    continue;
                }
                $res[$i->ID] = $i;
            }
            return $res;
        }

        $cashbookId = $this->getCashbookIdFromSkautisId($skautisEventId);

        return $this->queryBus->handle(new CategoryPairsQuery($cashbookId));
    }

    /**
     * CAMP ONLY
     * vrací ID kategorie pro příjmy od účastníků
     * @param string|NULL $category
     * @throws \Nette\InvalidStateException
     * @return int
     */
    public function getParticipantIncomeCategory(?int $skautisEventId = NULL, $category = NULL)
    {
        if ($this->type !== self::TYPE_CAMP) {
            throw new \InvalidArgumentException('This method is only for camps');
        }

        //@TODO: předělat na konstanty
        $catId = ($category == "adult") ? 3 : 1;
        foreach ($this->getCategories($skautisEventId) as $k => $val) {
            if (isset($val->ID_EventCampStatementType) && $val->ID_EventCampStatementType == $catId) {
                return $k;
            }
        }

        throw new \Nette\InvalidStateException("Chybí typ pro příjem od účastníků pro skupinu " . $category, 500);
    }

    /**
     * vrací soucet v kazdé kategorii
     * používá se pouze u táborů
     * @param int $skautisEventId
     * @return array (ID=>SUM)
     */
    public function getCategoriesSum(int $skautisEventId)
    {
        $db = $this->table->getTotalInCategories($this->getLocalId($skautisEventId)->toInt());
        $all = $this->getCategories($skautisEventId, FALSE);
        foreach (array_keys($all) as $key) {
            $all[$key] = array_key_exists($key, $db) ? $db[$key] : 0;
        }
        return $all;
    }

    /**
     * ověřuje konzistentnost dat mezi paragony a SkautISem
     * @param array|NULL $toRepair
     */
    public function isConsistent(int $skautisEventId, bool $repair = FALSE, array &$toRepair = NULL): bool
    {
        $sumSkautis = $this->getCategories($skautisEventId, FALSE);
        $cashbookId = $this->getCashbookIdFromSkautisId($skautisEventId);
        foreach ($this->getCategoriesSum($skautisEventId) as $catId => $ammount) {
            if ($ammount != $sumSkautis[$catId]->Ammount) {
                if ($repair) { //má se kategorie opravit?
                    $this->commandBus->handle(new UpdateCampCategoryTotal($cashbookId, $catId));
                } elseif ($toRepair !== NULL) {
                    $toRepair[$catId] = $ammount; //seznam ID vadných kategorií a jejich částek
                }
            }
        }
        return empty($toRepair);
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
