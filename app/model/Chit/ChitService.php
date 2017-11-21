<?php

namespace Model;

use Dibi\Row;
use eGen\MessageBus\Bus\EventBus;
use Model\Cashbook\ObjectType;
use Model\Services\Calculator;
use Model\Skautis\Mapper;
use Nette\Caching\IStorage;
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

    /** @var EventBus */
    private $eventBus;

    public function __construct(
        string $name,
        ChitTable $table,
        Skautis $skautIS,
        IStorage $cacheStorage,
        Mapper $skautisMapper,
        EventBus $eventBus
    )
    {
        parent::__construct($name, $skautIS, $cacheStorage);
        $this->table = $table;
        $this->skautisMapper = $skautisMapper;
        $this->eventBus = $eventBus;
    }

    /**
     * vrací jeden paragon
     * @param int $chitId
     * @return Row|FALSE
     */
    public function get($chitId)
    {
        $ret = $this->table->get($chitId);
        if ($ret instanceof Row && $this->type == self::TYPE_CAMP) {//doplnění kategorie u paragonu z tábora
            $categories = $this->getCategoriesPairs(NULL, $this->getSkautisId($ret->eventId));
            $ret->ctype = array_key_exists($ret->category, $categories['in']) ? "in" : "out";
        }
        return $ret;
    }

    /**
     * seznam paragonů k akci
     * @param int $skautisEventId
     * @return Row[]
     */
    public function getAll($skautisEventId, $onlyUnlocked = FALSE): array
    {
        $list = $this->table->getAll($this->getLocalId($skautisEventId), $onlyUnlocked);
        if (!empty($list) && $this->type == self::TYPE_CAMP) {
            $categories = $this->getCategoriesPairs(NULL, $skautisEventId);
            foreach ($list as $k => $i) {
                $i->ctype = array_key_exists($i->category, $categories['in']) ? "in" : "out";
                $i->clabel = $categories[$i->ctype][$i->category];
                $i->cshort = mb_substr($categories[$i->ctype][$i->category], 0, 5, 'UTF-8');

                $list[$k] = $i;
            }
            uasort($list, function ($a, $b) {
                if ($a->date == $b->date) {
                    if (($tmpCat = strlen($a->ctype) - strlen($b->ctype))) {
                        return $tmpCat; //pokud je název kratší, je dříve, platí pro in a out
                    }
                    return ($a->id < $b->id) ? -1 : 1;
                }
                return ($a->date < $b->date) ? -1 : 1;
            });
        }
        return $list;
    }

    /**
     * vrací pole paragonů s ID zadanými v $list
     * použití - hromadný tisk
     * @param int $skautisEventId
     * @param int[] $chitIds
     * @return array
     */
    public function getIn($skautisEventId, array $chitIds)
    {
        $ret = $this->table->getIn($this->getLocalId($skautisEventId), $chitIds);
        if ($this->type == self::TYPE_CAMP) {
            $categories = $this->getCategoriesPairs(NULL, $skautisEventId);
            foreach ($ret as $k => $v) {
                $ret[$k]->ctype = array_key_exists($ret[$k]->category, $categories['in']) ? "in" : "out";
            }
        }
        return $ret;
    }

    /**
     * upravit paragon - staci vyplnit data, ktera se maji zmenit
     * @param int $chitId
     * @param array|\ArrayAccess $val
     */
    public function update($chitId, $val): bool
    {
        $changeAbleData = ["eventId", "date", "num", "recipient", "purpose", "price", "category"];

        if (!is_array($val) && !($val instanceof \ArrayAccess)) {
            throw new \Nette\InvalidArgumentException("Values nejsou ve správném formátu");
        }
        $chit = $this->get($chitId);

        if (isset($val['id'])) {
            $val['id'] = $chitId;
        }

        $toChange = [];
        foreach ($changeAbleData as $name) {
            if (isset($val[$name])) {
                if ($name == 'price') {
                    $toChange['priceText'] = str_replace(",", ".", $val[$name]);
                    $toChange[$name] = Calculator::calculate($val[$name]);
                    continue;
                }
                $toChange[$name] = $val[$name];
            }
        }

        $ret = $this->table->update($chitId, $toChange);
        if (array_key_exists('eventId', $toChange)) {
            $chit['eventId'] = $toChange['eventId'];
        }
        if (array_key_exists('category', $toChange)) {
            $chit['category'] = $toChange['category'];
        }

        //category update
        if ($this->type == self::TYPE_CAMP) {
            $skautisEventId = $this->getSkautisId($chit->eventId);
            if ($skautisEventId !== 0) {
                $this->updateCategory($skautisEventId, $chit->category);
            }
        }

        return $ret;
    }

    /**
     * smazat paragon
     */
    public function delete(int $chitId, int $skautisEventId): bool
    {
        //TBD
        // $this->eventBus->handle(new ChitWasRemoved($this->event->ID_Unit, $user->ID, $user->Person, $id, $this->event->localId, $chit->purpose));

        return $this->table->delete($chitId, $this->getLocalId($skautisEventId));
    }

    /**
     * smazat všechny paragony dané akce
     * použití při rušení celé akce
     */
    public function deleteAll(int $skautisEventId): void
    {
        $this->table->deleteAll($this->getLocalId($skautisEventId));
    }

    /**
     * seznam všech kategorií pro daný typ
     * @param int|NULL $skautisEventId
     * @param bool $isEstimate - předpoklad?
     * @return array
     */
    public function getCategories($skautisEventId, bool $isEstimate = FALSE)
    {
        if ($this->type == self::TYPE_CAMP) {
            if (is_null($skautisEventId)) {
                throw new \InvalidArgumentException("Neplatný vstup \$skautisEventId=NULL pro " . __FUNCTION__);
            }
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
        } else {
            return $this->table->getCategoriesPairsByType($this->type);
        }
    }

    /**
     * vrací rozpočtové kategorie rozdělené na příjmy a výdaje (camp)
     * @param int|NULL $skautisEventId
     * @return array array(in=>(id=>DisplayName, ...), out=>(...))
     */
    public function getCategoriesPairs($typeInOut = NULL, $skautisEventId = NULL): array
    {
        $cacheId = __METHOD__ . $this->type . $skautisEventId . "_" . $typeInOut;
        if ($this->type === ObjectType::CAMP) {
            if (is_null($skautisEventId)) {
                throw new \InvalidArgumentException("Neplatný vstup \$skautisEventId=NULL pro " . __FUNCTION__);
            }
            $in = $out = [];
            if (!($categories = $this->loadSes($cacheId))) {
                foreach ($this->getCategories($skautisEventId, FALSE) as $i) {
                    if ($i->IsRevenue) {//výnosy?
                        $in[$i->ID] = $i->EventCampStatementType;
                    } else {
                        $out[$i->ID] = $i->EventCampStatementType;
                    }
                }
                $categories = ["in" => $in, "out" => $out];
                $this->saveSes($cacheId, $categories);
            }
            return is_null($typeInOut) ? $categories : $categories[$typeInOut];
        } else {
            return $this->table->getCategoriesPairsByType($this->type, $typeInOut);
        }
    }

    /**
     * vrací ID kategorie pro příjmy od účastníků
     * @param string|NULL $category
     * @throws \Nette\InvalidStateException
     * @return int
     */
    public function getParticipantIncomeCategory(?int $skautisEventId = NULL, $category = NULL)
    {
        $cacheId = __FUNCTION__;
        if ($this->type == self::TYPE_CAMP) {
            //@TODO: předělat na konstanty
            $catId = ($category == "adult") ? 3 : 1;
            foreach ($this->getCategories($skautisEventId) as $k => $val) {
                if ($val->ID_EventCampStatementType == $catId) {
                    return $k;
                }
            }
            throw new \Nette\InvalidStateException("Chybí typ pro příjem od účastníků pro skupinu " . $category, 500);
        } else {
            if (!($res = $this->loadSes($cacheId))) {
                foreach ($this->table->getGeneralCategories("in") as $c) {
                    if ($c->short == "hpd") {
                        $res = $c->id;
                        break;
                    }
                }
                $this->saveSes($cacheId, $res);
            }
            if (!$res) {
                throw new \Nette\InvalidStateException("Chybí kategorie paragonů pro příjem od účastníků", 500);
            }
            return $res;
        }
    }

    /**
     * vrací soucet v kazdé kategorii
     * používá se pouze u táborů
     * @param int $skautisEventId
     * @return array (ID=>SUM)
     */
    public function getCategoriesSum($skautisEventId)
    {
        $db = $this->table->getTotalInCategories($this->getLocalId($skautisEventId));
        $all = $this->getCategories($skautisEventId, FALSE);
        foreach (array_keys($all) as $key) {
            $all[$key] = array_key_exists($key, $db) ? $db[$key] : 0;
        }
        return $all;
    }

    /**
     * upraví celkový součet dané kategorie ve skautISu podle zadaných paragonů nebo podle parametru $ammout
     * @param int $skautisEventId
     * @param int $categoryId
     * @param float $ammout
     */
    public function updateCategory($skautisEventId, $categoryId, $ammout = NULL): void
    {
        if ($ammout === NULL) {
            $ammout = $this->table->getTotalInCategory($categoryId, $this->getLocalId($skautisEventId));
        }
        $this->skautis->event->EventCampStatementUpdate([
            "ID" => $categoryId,
            "ID_EventCamp" => $skautisEventId,
            "Ammount" => $ammout,
            "IsEstimate" => FALSE
        ], "eventCampStatement");
    }

    /**
     * ověřuje konzistentnost dat mezi paragony a SkautISem
     * @param array|NULL $toRepair
     */
    public function isConsistent(int $skautisEventId, bool $repair = FALSE, array &$toRepair = NULL): bool
    {
        $sumSkautis = $this->getCategories($skautisEventId, FALSE);

        foreach ($this->getCategoriesSum($skautisEventId) as $catId => $ammount) {
            if ($ammount != $sumSkautis[$catId]->Ammount) {
                if ($repair) { //má se kategorie opravit?
                    $this->updateCategory($skautisEventId, $catId, $ammount);
                } elseif ($toRepair !== NULL) {
                    $toRepair[$catId] = $ammount; //seznam ID vadných kategorií a jejich částek
                }
            }
        }
        return empty($toRepair);
    }

    /**
     * je akce celkově v záporu?
     * @param int $skautisEventId
     */
    public function eventIsInMinus(int $skautisEventId): bool
    {
        $data = $this->table->eventIsInMinus($this->getLocalId($skautisEventId));
        return @(($data["in"] - $data["out"]) < 0) ? TRUE : FALSE; //@ potlačuje chyby u neexistujicich indexů "in" a "out"
    }

    /**
     * [[ UNUSED ]]
     * uzavře paragon proti editaci, měnit lze jen kategorie z rozpočtu jednotky
     * @param int $oid
     * @param int $chitId
     * @param int $userId
     */
    public function lock($oid, $chitId, $userId): void
    {
        $this->table->lock($oid, $chitId, $userId);
    }

    /**
     * [[ UNUSED ]]
     * @param int $oid
     * @param int $chitId
     * @return \Dibi\Result|int
     */
    public function unlock($oid, $chitId)
    {
        return $this->table->unlock($oid, $chitId);
    }

    /**
     * @param int $oid
     * @param int $userId
     * @return \Dibi\Result|int
     */
    public function lockEvent($oid, $userId)
    {
        return $this->table->lockEvent($oid, $userId);
    }

    public function moveChits(array $chits, int $originEventId, string $originEventType, int $newEventId, string $newEventType): void
    {
        foreach ($chits as $chitId) {
            $toUpdate = ["eventId" => $this->getLocalId($newEventId, $newEventType)];
            $chit = $this->get($chitId);
            if ($this->getLocalId($originEventId, $originEventType) !== $chit['eventId']) {
                throw new \InvalidArgumentException("Zvolený doklad ($chitId) nenáleží původní akci ($originEventId)");
            }
            //pokud nejsou obe knihy od výprav, tak nastav kategorii na neurčitou kategorii
            if ($originEventType !== $newEventType || $originEventType !== "general") {
                $toUpdate["category"] = $chit['ctype'] === "out" ? self::CHIT_UNDEFINED_OUT : self::CHIT_UNDEFINED_IN;
            }
            $this->update($chitId, $toUpdate);
        }
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

    private function getSkautisId(int $localEventId): ?int
    {
        return $this->skautisMapper->getSkautisId($localEventId, $this->type);
    }

    private function getLocalId(int $skautisEventId, string $type = NULL): int
    {
        return $this->skautisMapper->getLocalId($skautisEventId, $type ?? $this->type);
    }

    public function getCashbookIdFromSkautisId(int $skautisid): int
    {
        return $this->getLocalId($skautisid);
    }

}
