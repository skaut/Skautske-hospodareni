<?php

namespace Model;

use Dibi\Row;
use Model\Skautis\Mapper;
use Nette\Caching\IStorage;
use Skautis\Skautis;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class ChitService extends MutableBaseService
{

    const CHIT_UNDEFINED_OUT = 8;
    const CHIT_UNDEFINED_IN = 12;

    /** @var Mapper */
    private $skautisMapper;

    /** @var ChitTable */
    private $table;

    public function __construct(string $name, ChitTable $table, Skautis $skautIS, IStorage $cacheStorage, Mapper $skautisMapper)
    {
        parent::__construct($name, $skautIS, $cacheStorage);
        $this->table = $table;
        $this->skautisMapper = $skautisMapper;
    }

    /**
     * vrací jeden paragon
     * @param int $chitId
     * @return Row
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
     * @return \stdClass[]
     */
    public function getAll($skautisEventId, $onlyUnlocked = FALSE)
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
     * @param int[] $list - pole id
     * @return array
     */
    public function getIn($skautisEventId, $list)
    {
        $ret = $this->table->getIn($this->getLocalId($skautisEventId), (array)$list);
        if ($this->type == self::TYPE_CAMP) {
            $categories = $this->getCategoriesPairs(NULL, $skautisEventId);
            foreach ($ret as $k => $v) {
                $ret[$k]->ctype = array_key_exists($ret[$k]->category, $categories['in']) ? "in" : "out";
            }
        }
        return $ret;
    }

    /**
     * přidat paragon
     * @param int $skautisEventId
     * @param array|\ArrayAccess $val - údaje
     * @throws \Nette\InvalidArgumentException
     * @return bool
     */
    public function add($skautisEventId, $val): bool
    {
        $localEventId = $this->getLocalId($skautisEventId);

        if (!is_array($val) && !($val instanceof \ArrayAccess)) {
            throw new \Nette\InvalidArgumentException("Values nejsou ve správném formátu");
        }

        $values = [
            "eventId" => $localEventId,
            "date" => $val['date'],
            "recipient" => $val['recipient'],
            "purpose" => $val['purpose'],
            "price" => $this->solveString($val['price']),
            "priceText" => str_replace(",", ".", $val['price']),
            "category" => $val['category'],
            "num" => isset($val['num']) ? $val['num'] : "", //$val['num'] != "" ? str_pad((int) $val['num'],5,"0",STR_PAD_LEFT) : null
        ];

        $ret = $this->table->add($values);

        if ($this->type == self::TYPE_CAMP) {
            try {
                $this->updateCategory($skautisEventId, $val['category']);
            } catch (\Skautis\Wsdl\PermissionException $ex) {

            }
        }

        return (bool)$ret;
    }

    /**
     * @param int $chitId
     * @return string
     */
    public function generateNumber($chitId): string
    {
        $chit = $this->get($chitId);
        $categories = $this->getCategoriesPairs(NULL, $this->type == self::TYPE_CAMP ? $this->getSkautisId($chit->eventId) : NULL);

        if (array_key_exists($chit->category, $categories['in'])) {
            $type = 'in';
            $res = "1";
        } else {
            $type = 'out';
            $res = "2";
        }
        $res .= $this->table->generateNumber($chit->eventId, array_keys($categories[$type]));
        return $res;
    }

    /**
     * upravit paragon - staci vyplnit data, ktera se maji zmenit
     * @param int $chitId
     * @param array|\ArrayAccess $val
     * @return bool
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
                    $toChange[$name] = $this->solveString($val[$name]);
                    continue;
                }
                $toChange[$name] = $val[$name];
            }
        }
        $ret = $this->table->update($chitId, $toChange);
        //category update
        if ($this->type == self::TYPE_CAMP) {
            $skautisEventId = $this->getSkautisId($chit->eventId);
            //@TODO: zkontrolovat proč to je tady 2x
            $this->updateCategory($skautisEventId, $chit->category);
            if (isset($val["category"])) {
                $this->updateCategory($skautisEventId, $val["category"]);
            }
        }
        return $ret;
    }

    /**
     * smazat paragon
     * @param int $chitId
     * @param int $skautisEventId
     * @return bool
     */
    public function delete($chitId, $skautisEventId): bool
    {
        return $this->table->delete($chitId, $this->getLocalId($skautisEventId));
    }

    /**
     * smazat všechny paragony dané akce
     * použití při rušení celé akce
     * @param int $skautisEventId
     */
    public function deleteAll($skautisEventId): void
    {
        $this->table->deleteAll($this->getLocalId($skautisEventId));
    }

    /**
     * seznam všech kategorií pro daný typ
     * @param int $skautisEventId
     * @param bool $isEstimate - předpoklad?
     * @return array
     */
    public function getCategories($skautisEventId, $isEstimate = FALSE)
    {
        if ($this->type == self::TYPE_CAMP) {
            if (is_null($skautisEventId)) {
                throw new \InvalidArgumentException("Neplatný vstup \$skautisEventId=NULL pro " . __FUNCTION__);
            }
            //přidání kategorií k táborům
            $res = [//8 a 12 jsou ID použitá i u výprav
                self::CHIT_UNDEFINED_OUT => (object)["ID" => self::CHIT_UNDEFINED_OUT, "IsRevenue" => FALSE, "EventCampStatementType"=> "Neurčeno", "Ammount" => 0],
                self::CHIT_UNDEFINED_IN  => (object)["ID" => self::CHIT_UNDEFINED_IN,  "IsRevenue" => TRUE,  "EventCampStatementType"=> "Neurčeno", "Ammount" => 0],
            ];
            foreach ($this->skautis->event->EventCampStatementAll(["ID_EventCamp" => $skautisEventId, "IsEstimate" => $isEstimate]) as $i) { //prepisuje na tvar s klíčem jako ID
                if ($isEstimate == FALSE && $i->ID_EventCampStatementType == 15) { //15 == Rezerva
                    continue;
                }
                $res[$i->ID] = $i;
            }
            return $res;
        } else {
            return $this->table->getGeneralCategories();
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
        if ($this->type == self::TYPE_CAMP) {
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
            if (($res = $this->cache->load($cacheId)) == NULL) {
                $res = $this->table->getGeneralCategoriesPairs($typeInOut);
                $this->cache->save($cacheId, $res, [
                    \Nette\Caching\Cache::EXPIRE => '+ 2 days',
                ]);
            }
            return $res;
        }
    }

    /**
     * vrací ID kategorie pro příjmy od účastníků
     * @param int|NULL $skautisEventId
     * @param string|NULL $category
     * @throws \Nette\InvalidStateException
     * @return int
     */
    public function getParticipantIncomeCategory($skautisEventId = NULL, $category = NULL)
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
            $ammout = (int)$this->table->getTotalInCategory($categoryId, $this->getLocalId($skautisEventId));
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
     * @param int $skautisEventId
     * @param bool $repair
     * @param array|NULL $toRepair
     * @return boolean
     */
    public function isConsistent($skautisEventId, $repair = FALSE, array &$toRepair = NULL)
    {
        $sumSkautis = $this->getCategories($skautisEventId, FALSE);

        foreach ($this->getCategoriesSum($skautisEventId) as $catId => $ammount) {
            if ($ammount != $sumSkautis[$catId]->Ammount) {
                if ($repair) { //má se kategorie opravit?
                    $this->updateCategory($skautisEventId, $catId, $ammount);
                } elseif($toRepair !== NULL) {
                    $toRepair[$catId] = $ammount; //seznam ID vadných kategorií a jejich částek
                }
            }
        }
        return empty($toRepair);
    }

    /**
     * je akce celkově v záporu?
     * @param int $skautisEventId
     * @return bool
     */
    public function eventIsInMinus($skautisEventId)
    {
        $data = $this->table->eventIsInMinus($this->getLocalId($skautisEventId));
        return @(($data["in"] - $data["out"]) < 0) ? TRUE : FALSE; //@ potlačuje chyby u neexistujicich indexů "in" a "out"
    }

    /**
     * vyhodnotí řetězec obsahující čísla, +, *
     * @param string $str - výraz k výpčtu
     * @return int
     */
    private function solveString($str)
    {
        preg_match_all('/(?P<cislo>-?[0-9]+([.][0-9]{1,})?)(?P<operace>[\+\*]+)?/', str_replace(",", ".", $str), $matches);
        $maxIndex = count($matches['cislo']);
        foreach ($matches['operace'] as $index => $op) { //vyřeší operaci násobení
            if ($op == "*" && $index + 1 <= $maxIndex) {
                $matches['cislo'][$index + 1] = $matches['cislo'][$index] * $matches['cislo'][$index + 1];
                $matches['cislo'][$index] = 0;
            }
        }
        return array_sum($matches['cislo']);
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

    public function moveChit($chitId, $chitType, $originEventType, $newEventId, $newEventType): void
    {
        $toUpdate = ["eventId" => $this->getLocalId($newEventId, $newEventType)];
        //pokud nejsou obe kniny od výprav, tak nastav kategorii na neurčitou kategorii
        if($originEventType !== $newEventType || $originEventType !== "general") {
            $toUpdate["category"] = $chitType === "out" ? self::CHIT_UNDEFINED_OUT : self::CHIT_UNDEFINED_IN;
        }
        $this->update($chitId, $toUpdate);
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

    public function getSkautisId(int $localEventId): ?int
    {
        return $this->skautisMapper->getSkautisId($localEventId, $this->type);
    }

    private function getLocalId(int $skautisEventId, string $type = NULL): int
    {
        return $this->skautisMapper->getLocalId($skautisEventId, $type ?? $this->type);
    }

}
