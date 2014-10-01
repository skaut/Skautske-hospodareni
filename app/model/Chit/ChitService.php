<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class ChitService extends MutableBaseService {

    /**
     * @var EventService
     */
    protected $objectService;

    public function __construct($name, $skautIS, $cacheStorage, $connection, $objectService) {
        parent::__construct($name, $skautIS, $cacheStorage, $connection);
        $this->objectService = $objectService;
    }

    /**
     * vrací jeden paragon
     * @param type $chitId
     * @return \DibiRow
     */
    public function get($chitId) {
        $ret = $this->table->get($chitId);
        if ($ret instanceof \DibiRow && $this->type == self::TYPE_CAMP) {//doplnění kategorie u paragonu z tábora
            $categories = $this->getCategoriesPairs(NULL, $this->getSkautisId($ret->eventId));
            $ret->ctype = array_key_exists($ret->category, $categories['in']) ? "in" : "out";
        }
        return $ret;
    }

    /**
     * seznam paragonů k akci
     * @param type $skautisEventId
     * @return type 
     */
    public function getAll($skautisEventId, $onlyUnlocked = FALSE) {
        $list = $this->table->getAll($this->getLocalId($skautisEventId), $onlyUnlocked);
        if (!empty($list) && $this->type == self::TYPE_CAMP) {
            $categories = $this->getCategoriesPairs(NULL, $skautisEventId);
            foreach ($list as $k => $i) {
                $i->ctype = array_key_exists($i->category, $categories['in']) ? "in" : "out";
                $i->clabel = $categories[$i->ctype][$i->category];
                $i->cshort = mb_substr($categories[$i->ctype][$i->category], 0, 5);

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
     * @param type $skautisEventId
     * @param type $list - pole id
     * @return array
     */
    public function getIn($skautisEventId, $list) {
        $ret = $this->table->getIn($this->getLocalId($skautisEventId), (array) $list);
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
     * @param type $skautisEventId
     * @param array|ArrayAccess $val - údaje
     * @return type 
     */
    public function add($skautisEventId, $val) {
        $localEventId = $this->getLocalId($skautisEventId);

        if (!is_array($val) && !($val instanceof \ArrayAccess)) {
            throw new \Nette\InvalidArgumentException("Values nejsou ve správném formátu");
        }

        $values = array(
            "eventId" => $localEventId,
            "date" => $val['date'],
            "recipient" => $val['recipient'],
            "purpose" => $val['purpose'],
            "price" => $this->solveString($val['price']),
            "priceText" => str_replace(",", ".", $val['price']),
            "category" => $val['category'],
            "num" => isset($val['num']) ? $val['num'] : "", //$val['num'] != "" ? str_pad((int) $val['num'],5,"0",STR_PAD_LEFT) : null
        );

        $ret = $this->table->add($values);
        //doplnění čísla dokladu
        //$this->table->update($ret, array("num"=>$this->generateNumber($ret)));
        if ($this->type == self::TYPE_CAMP) {
            try {
                $this->updateCategory($skautisEventId, $val['category']);
            } catch (\SkautIS\Exception\PermissionException $ex) {
                
            }
        }

        return $ret;
    }

    public function generateNumber($chitId) {
        $chit = $this->get($chitId);
//        dump($chit);
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
     * @param type $chitId
     * @param ArrayAccess $val
     * @return type 
     */
    public function update($chitId, $val) {
        $changeAbleData = array("date", "num", "recipient", "purpose", "price", "category");

        if (!is_array($val) && !($val instanceof \ArrayAccess)) {
            throw new \Nette\InvalidArgumentException("Values nejsou ve správném formátu");
        }
        $chit = $this->get($chitId);

        if (isset($val['id'])) {
            $val['id'] = $chitId;
        }

        $toChange = array();
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
     * @param type $chitId
     * @param type $skautisEventId
     * @return type 
     */
    public function delete($chitId, $skautisEventId) {
        return $this->table->delete($chitId, $this->getLocalId($skautisEventId));
    }

    /**
     * smazat všechny paragony dané akce
     * použití při rušení celé akce
     * @param type $skautisEventId
     * @return type 
     */
    public function deleteAll($skautisEventId) {
        return $this->table->deleteAll($this->getLocalId($skautisEventId));
    }

    /**
     * seznam všech kategorií pro daný typ
     * @param type $skautisEventId
     * @param bool $isEstimate - předpoklad?
     * @return array
     */
    public function getCategories($skautisEventId, $isEstimate = false) {
        if ($this->type == self::TYPE_CAMP) {
            if (is_null($skautisEventId)) {
                throw new \InvalidArgumentException("Neplatný vstup \$skautisEventId=NULL pro " . __FUNCTION__);
            }
            $res = array();
            foreach ($this->skautIS->event->EventCampStatementAll(array("ID_EventCamp" => $skautisEventId, "IsEstimate" => $isEstimate)) as $i) { //prepisuje na tvar s klíčem jako ID
                if ($isEstimate == false && $i->ID_EventCampStatementType == 15) {//$i->ID_EventCampStatementType == 15 => Rezerva v rozpoctu
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
     * @param type $skautisEventId
     * @return array(in=>(id=>DisplayName, ...), out=>(...))
     */
    public function getCategoriesPairs($typeInOut = NULL, $skautisEventId = NULL) {
        $cacheId = __METHOD__ . $this->type . $skautisEventId . "_" . $typeInOut;
        if ($this->type == self::TYPE_CAMP) {
            if (is_null($skautisEventId)) {
                throw new \InvalidArgumentException("Neplatný vstup \$skautisEventId=NULL pro " . __FUNCTION__);
            }
            $in = $out = array();
            if (!($categories = $this->loadSes($cacheId))) {
                foreach ($this->getCategories($skautisEventId, false) as $i) {
                    if ($i->IsRevenue) {//výnosy?
                        $in[$i->ID] = $i->EventCampStatementType;
                    } else {
                        $out[$i->ID] = $i->EventCampStatementType;
                    }
                }
                $categories = array("in" => $in, "out" => $out);

                $this->saveSes($cacheId, $categories);
            }
            return is_null($typeInOut) ? $categories : $categories[$typeInOut];
        } else {
            if (($res = $this->cache->load($cacheId)) == NULL) {
                $res = $this->table->getGeneralCategoriesPairs($typeInOut);
                $this->cache->save($cacheId, $res, array(
                    \Nette\Caching\Cache::EXPIRE => '+ 2 days',
                ));
            }
            return $res;
        }
    }

    /**
     * vrací ID kategorie pro příjmy od účastníků
     * @return type
     * @throws \Nette\InvalidStateException
     */
    public function getParticipantIncomeCategory($skautisEventId = NULL, $category = NULL) {
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
     * @param type $skautisEventId
     * @return (ID=>SUM)
     */
    public function getCategoriesSum($skautisEventId) {
        if ($this->type != self::TYPE_CAMP) {
            trigger_error("Metoda '" . __METHOD__ . "' je určená pouze pro tábory.", E_USER_NOTICE);
        }
        $db = $this->table->getTotalInCategories($this->getLocalId($skautisEventId));
        $all = $this->getCategories($skautisEventId, false);
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
    public function updateCategory($skautisEventId, $categoryId, $ammout = NULL) {
        if ($ammout === NULL) {
            $ammout = (int) $this->table->getTotalInCategory($categoryId, $this->getLocalId($skautisEventId));
        }
        $this->skautIS->event->EventCampStatementUpdate(array(
            "ID" => $categoryId,
            "ID_EventCamp" => $skautisEventId,
            "Ammount" => $ammout,
            "IsEstimate" => false
                ), "eventCampStatement");
    }

    /**
     * ověřuje konzistentnost dat mezi paragony a SkautISem
     * @param type $skautisEventId
     * @return boolean 
     */
    public function isConsistent($skautisEventId, $repair = false, &$toRepair = NULL) {
        $sumSkautIS = $this->getCategories($skautisEventId, false);
        //$toRepair = array();
        foreach ($this->getCategoriesSum($skautisEventId) as $catId => $ammount) {
            if ($ammount != $sumSkautIS[$catId]->Ammount) {
                if ($repair) { //má se kategorie opravit?
                    $this->updateCategory($skautisEventId, $catId, $ammount);
                } else {
                    $toRepair[$catId] = $ammount; //seznam ID vadných kategorií a jejich částek
                }
            }
        }
        return empty($toRepair) ? true : false;
    }

    /**
     * je akce celkově v záporu?
     * @param type $skautisEventId
     * @return bool
     */
    public function eventIsInMinus($skautisEventId) {
        $data = $this->table->eventIsInMinus($this->getLocalId($skautisEventId));
        return @(($data["in"] - $data["out"]) < 0) ? true : false; //@ potlačuje chyby u neexistujicich indexů "in" a "out"
    }

    /**
     * vyhodnotí řetězec obsahující čísla, +, *
     * @param string $str - výraz k výpčtu
     * @return int 
     */
    function solveString($str) {
        preg_match_all('/(?P<cislo>[0-9]+([.][0-9]{1,})?)(?P<operace>[\+\*]+)?/', str_replace(",", ".", $str), $matches);
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
     * uzavře paragon proti editaci, měnit lze jen kategorie z rozpočtu jednotky
     * @param type $oid
     * @param type $chitId
     * @param type $userId
     * @return type
     */
    public function lock($oid, $chitId, $userId) {
        return $this->table->lock($oid, $chitId, $userId);
    }

    public function unlock($oid, $chitId, $userid = NULL) {
        return $this->table->unlock($oid, $chitId);
    }

    /**
     * nastavuje kategorie z rozpočtu
     * @param int $chitId
     * @param int $in
     * @param int $out
     * @return type
     */
    public function setBudgetCategories($chitId, $in = NULL, $out = NULL) {
        return $this->table->update($chitId, array("budgetCategoryIn" => $in, "budgetCategoryOut" => $out));
    }

    public function getBudgetCategoriesSummary($categories) {
        return $this->table->getBudgetCategoriesSummary(array_keys($categories['in']), 'in') + $this->table->getBudgetCategoriesSummary(array_keys($categories['out']), 'out');
    }

    /**
     * 
     * @param type $localEventId
     * @return type
     */
    function getSkautisId($localEventId, $type = NULL) {
        return $this->objectService->getSkautisId($localEventId, $this->type);
    }

    function getLocalId($skautisEventId, $type = NULL) {
        return $this->objectService->getLocalId($skautisEventId, $this->type);
    }

}
