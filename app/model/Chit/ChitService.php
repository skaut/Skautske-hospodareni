<?php

/**
 * @author Hána František
 */
class ChitService extends MutableBaseService {

    /**
     * @var EventService
     */
    protected $eventService;

    public function __construct($name, $longName, $expire, $skautIS, $cacheStorage, $eventService) {
        parent::__construct($name, $longName, $expire, $skautIS, $cacheStorage, $eventService);
        /** @var ChitTable */
        $this->table = new ChitTable();
        $this->eventService = $eventService;
    }

    /**
     * vrací jeden paragon
     * @param type $chitId
     * @return \DibiRow
     */
    public function get($chitId) {
        $ret = $this->table->get($chitId);
        if ($ret instanceof DibiRow && self::$type == self::TYPE_CAMP) {//doplnění kategorie u paragonu z tábora
            $categories = $this->getCategoriesCampPairs($this->eventService->getSkautisId($ret->eventId));
            $ret->ctype = array_key_exists($ret->category, $categories['in']) ? "in" : "out";
        }
        return $ret;
    }

    /**
     * seznam paragonů k akci
     * @param type $skautisEventId
     * @return type 
     */
    public function getAll($skautisEventId) {
        $list = $this->table->getAll($this->eventService->getLocalId($skautisEventId));
        if (!empty($list) && self::$type == self::TYPE_CAMP) {
            $categories = $this->getCategoriesCampPairs($skautisEventId);
            foreach ($list as $k => $i) {
                $i->ctype = array_key_exists($i->category, $categories['in']) ? "in" : "out";
                $i->clabel = array_key_exists($i->category, $categories['in']) ? $categories['in'][$i->category] : $categories['out'][$i->category];
                $i->cshort = array_key_exists($i->category, $categories['in']) ? $categories['in'][$i->category] : $categories['out'][$i->category];
                $i->cshort = mb_substr($i->cshort, 0, 5);
                $list[$k] = $i;
            }
            uasort($list, function ($a, $b) {
                        if ($a->date == $b->date) {
                            if (($tmpCat = strlen($a->ctype) - strlen($b->ctype))) {
                                return $tmpCat; //pokud je název kratší, je dříve
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
        $ret = $this->table->getIn($this->eventService->getLocalId($skautisEventId), (array) $list);
        if (self::$type == self::TYPE_CAMP) {
            $categories = $this->getCategoriesCampPairs($skautisEventId);
            foreach ($ret as $k => $v)
                $ret[$k]->ctype = array_key_exists($ret[$k]->category, $categories['in']) ? "in" : "out";
        }
        return $ret;
    }

    /**
     * přidat paragon
     * @param type $actionId
     * @param array|ArrayAccess $val - údaje
     * @return type 
     */
    public function add($skautisEventId, $val) {
        $localEventId = $this->eventService->getLocalId($skautisEventId);
        if (!is_array($val) && !($val instanceof ArrayAccess))
            throw new InvalidArgumentException("Values nejsou ve správném formátu");

        $values = array(
            "eventId" => $localEventId,
            "date" => $val['date'],
            "recipient" => $val['recipient'],
            "purpose" => $val['purpose'],
            "price" => $this->solveString($val['price']),
            "priceText" => str_replace(",", ".", $val['price']),
            "category" => $val['category'],
            "num" => $val['num'],//$val['num'] != "" ? str_pad((int) $val['num'],5,"0",STR_PAD_LEFT) : null
        );

        $ret = $this->table->add($values);
        //doplnění čísla dokladu
        //$this->table->update($ret, array("num"=>$this->generateNumber($ret)));
        if (self::$type == self::TYPE_CAMP) {
            $this->updateCategory($skautisEventId, $val['category']);
        }

        return $ret;
    }
    
        public function generateNumber($chitId){
        $chit = $this->get($chitId);
//        dump($chit);
        if(self::$type == self::TYPE_CAMP){
            $categories = $this->getCategoriesCampPairs($this->eventService->getSkautisId($chit->eventId));
        } else {//GeneralEvent
            $categories = array('in'=>$this->getCategoriesIn(), 'out'=>$this->getCategoriesOut());
        }
        
        if(array_key_exists($chit->category, $categories['in'])){
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

        if (!is_array($val) && !($val instanceof ArrayAccess)) {
            throw new InvalidArgumentException("Values nejsou ve správném formátu");
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
        if (self::$type == self::TYPE_CAMP) {
            $skautisEventId = $this->eventService->getSkautisId($chit->eventId);
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
     * @param type $localEventId
     * @return type 
     */
    public function delete($chitId, $skautisEventId) {
        return $this->table->delete($chitId, $this->eventService->getLocalId($skautisEventId));
    }

    /**
     * smazat všechny paragony dané akce
     * použití při rušení celé akce
     * @param type $localEventId
     * @return type 
     */
    public function deleteAll($skautisEventId) {
        return $this->table->deleteAll($this->eventService->getLocalId($skautisEventId));
    }

    /**
     * vrací všechny kategorie akcí
     * @param bool $all - vracet vsechny informace o kategoriích?
     * @return array
     */
    public function getCategories($all = FALSE) {
        if ($all) {
            return $this->table->getCategoriesAll();
        }
        return $this->table->getCategories();
    }

    /**
     * vrací prijmové kategorie výprav
     * @return array 
     */
    public function getCategoriesIn() {
        $cacheId = __CLASS__ . "/" . __FUNCTION__;
        if (($res = $this->cache->load($cacheId)) == NULL) {
            $res = $this->table->getCategories("in");
            $this->cache->save($cacheId, $res, array(
                \Nette\Caching\Cache::EXPIRE => '+ 2 days',
            ));
        }
        return $res;
    }

    /**
     * vrací výdajové kategorie výprav
     * @return array 
     */
    public function getCategoriesOut() {
        $cacheId = __CLASS__ . "/" . __FUNCTION__;
        if (($res = $this->cache->load($cacheId)) == NULL) {
            $res = $this->table->getCategories("out");
            $this->cache->save($cacheId, $res, array(
                \Nette\Caching\Cache::EXPIRE => '+ 2 days',
            ));
        }
        return $res;
    }

    /**
     * vrací ID kategorie pro příjmy od účastníků
     * @return type
     * @throws \Nette\InvalidStateException
     */
    public function getEventCategoryParticipant() {
        $cacheId = __FUNCTION__;
        if (!($res = $this->loadSes($cacheId))) {
            foreach ($this->table->getCategoriesAll("in") as $c) {
                if ($c->short == "hpd") {
                    $res = $c->id;
                    break;
                }
            }
            $this->saveSes($cacheId, $res);
        }
        if (!$res)
            throw new \Nette\InvalidStateException("Chybí typ pro příjem od účastníků", 500);
        return $res;
    }

    /*     * ******** CAMP CATEGORIES *********** */

    /**
     * seznam všech kategorií ze skautISu
     * @param type $skautisEventId
     * @param bool $isEstimate - odhadovaný?
     * @return array(ID=>category, ...)
     */
    public function getCategoriesCamp($skautisEventId, $isEstimate = false) {
        $tmp = $this->skautIS->event->EventCampStatementAll(array("ID_EventCamp" => $skautisEventId, "IsEstimate" => $isEstimate));
        //$tmp = $this->skautIS->event->EventCampStatementAll(array("ID_EventCamp" => $actionId, "IsEstimate" => false));
        $res = array();
        foreach ($tmp as $i) { //prepisuje na tvar s klíčem jako ID
            if ($isEstimate == false && $i->ID_EventCampStatementType == 15)
                continue;
            $res[$i->ID] = $i;
        }
        return $res;
    }

    /**
     * vrací rozpočtové kategorie rozdělené na příjmy a výdaje (camp)
     * @param type $skautisEventId
     * @return array(in=>(id, ...), out=>(...))
     */
    public function getCategoriesCampPairs($skautisEventId) {
        $in = $out = array();

        $cacheId = __FUNCTION__ . "_" . $skautisEventId;
        if (!($all = $this->loadSes($cacheId))) {
            foreach ($this->getCategoriesCamp($skautisEventId, false) as $i) {
                if ($i->IsRevenue) {//výnosy?
                    $in[$i->ID] = $i->EventCampStatementType;
                } else {
                    $out[$i->ID] = $i->EventCampStatementType;
                }
            }
            $all = array("in" => $in, "out" => $out);
            $this->saveSes($cacheId, $all);
        }
        return $all;
    }

    /**
     * vrací soucet v kazdé kategorii
     * @param type $skautisEventId
     * @return (ID=>SUM)
     */
    public function getCategoriesCampSum($skautisEventId) {
        $db = $this->table->getTotalInCategories($this->eventService->getLocalId($skautisEventId));
        $all = $this->getCategoriesCamp($skautisEventId, false);
        foreach ($all as $key => $item) {
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
        if ($ammout === NULL)
            $ammout = (int) $this->table->getTotalInCategory($categoryId, $this->eventService->getLocalId($skautisEventId));
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
        $sumSkautIS = $this->getCategoriesCamp($skautisEventId, false);
        //$toRepair = array();
        foreach ($this->getCategoriesCampSum($skautisEventId) as $catId => $ammount) {
            if ($ammount != $sumSkautIS[$catId]->Ammount) {
                if ($repair) { //má se kategorie oprazvit?
                    $this->updateCategory($skautisEventId, $catId, $ammount);
                } else {
                    $toRepair[$catId] = $ammount; //seznam ID vadných kategorií a jejich částek
                }
            }
        }
        return empty($toRepair) ? true : false;
    }

    /**
     * vrací číslo kategirie účastníka
     * @return int
     * @throws \Nette\InvalidStateException
     */
    public function getCampCategoryParticipant($skautisEventId, $category) {
        //@TODO: předělat na konstanty
        $catId = ($category == "adult") ? 3 : 1;

        foreach ($this->getCategoriesCamp($skautisEventId) as $k => $val) {
            if ($val->ID_EventCampStatementType == $catId) {
                return $k;
            }
        }
        throw new \Nette\InvalidStateException("Chybí typ pro příjem od účastníků pro skupinu " . $category, 500);
    }

    /*     * ******** END CAMP CATEGORIES *********** */

    /**
     * je akce celkově v záporu?
     * @param type $skautisEventId
     * @return bool
     */
    public function isInMinus($skautisEventId) {
        $data = $this->table->isInMinus($this->eventService->getLocalId($skautisEventId));
        return @(($data["in"] - $data["out"]) < 0) ? true : false; //@ potlačuje chyby u neexistujicich indexů "in" a "out"
    }

    /**
     * vyhodnotí řetězec obsahující čísla, +, *
     * @param string $str - výraz k výpčtu
     * @return int 
     */
    function solveString($str) {
        $str = str_replace(",", ".", $str); //prevede desetinou carku na tecku
        preg_match_all('/(?P<cislo>[0-9]+([.][0-9]{1,})?)(?P<operace>[\+\*]+)?/', $str, $matches);
        $maxIndex = count($matches['cislo']);
        foreach ($matches['operace'] as $index => $op) { //vyřeší operaci násobení
            if ($op == "*" && $index + 1 <= $maxIndex) {
                $matches['cislo'][$index + 1] = $matches['cislo'][$index] * $matches['cislo'][$index + 1];
                $matches['cislo'][$index] = 0;
            }
        }
        return array_sum($matches['cislo']);
    }

}