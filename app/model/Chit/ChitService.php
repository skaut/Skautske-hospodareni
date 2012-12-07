<?php

/**
 * @author Hána František
 */
class ChitService extends MutableBaseService {

    public function __construct($name, $longName, $expire, $skautIS, $cacheStorage) {
        parent::__construct($name, $longName, $expire, $skautIS, $cacheStorage);
        /** @var ChitTable */
        $this->table = new ChitTable();
    }

    public function get($id) {
        $ret = $this->table->get($id);
        if ($ret instanceof DibiRow && $ret->type == "camp") {
            $categories = $this->getCategoriesCampPairs($ret->actionId);
            $ret->ctype = array_key_exists($ret->category, $categories['in']) ? "in" : "out";
        }
        return $ret;
    }

    /**
     * seznam paragonů k akci
     * @param type $actionId
     * @return type 
     */
    public function getAll($actionId) {
        $list = $this->table->getAll($actionId);
        if (!empty($list) && $list[0]->type == "camp") {
            $categories = $this->getCategoriesCampPairs($actionId);
            foreach ($list as $k => $i) {
                $i->ctype = array_key_exists($i->category, $categories['in']) ? "in" : "out";
                $i->clabel = array_key_exists($i->category, $categories['in']) ? $categories['in'][$i->category] : $categories['out'][$i->category];
                $i->cshort = array_key_exists($i->category, $categories['in']) ? $categories['in'][$i->category] : $categories['out'][$i->category];
                $i->cshort = mb_substr($i->cshort, 0, 5);
                $list[$k] = $i;
            }
        }

        return $list;
    }

    /**
     * 
     * @param type $actionId
     * @return type 
     * @deprecated
     */
//    public function getAllOut($actionId) {
//        $data = $this->table->getAll($actionId);
//        $res = array();
//        foreach ($data as $i) {
//            if ($i->ctype == "out")
//                $res[] = $i;
//        }
//        return $res;
//    }
//
//    /**
//     * vrací seznam příjmových dokladů
//     * @param type $actionId
//     * @return array 
//     */
//    public function getAllIncome($actionId) {
//        $data = $this->table->getAll($actionId);
//        $res = array();
//        foreach ($data as $i) {
//            if ($i->ctype == "in")
//                $res[] = $i;
//        }
//        return $res;
//    }

    /**
     * vrací pole paragonů s ID zadanými v $list
     * použití - hromadný tisk
     * @param type $actionId
     * @param type $list - pole id
     * @return array
     */
    public function getIn($actionId, $list) {
        $ret = $this->table->getIn($actionId, (array) $list);
        if ($ret[0] instanceof DibiRow && $ret[0]->type == "camp") {
            $categories = $this->getCategoriesCampPairs($actionId);
            foreach ($ret as $k=>$v)
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
    public function add($actionId, $val) {

        if (!is_array($val) && !($val instanceof ArrayAccess))
            throw new InvalidArgumentException("Values nejsou ve správném formátu");

        $values = array(
            "actionId" => $actionId,
            "date" => $val['date'],
            "recipient" => $val['recipient'],
            "purpose" => $val['purpose'],
            "price" => $this->solveString($val['price']),
            "priceText" => $val['price'],
            "type" => strtolower(self::$typeName),
            "category" => $val['category'],
        );

        $ret = $this->table->add($values);
        if ($values['type'] == "camp") {
            $this->updateCategory($actionId, $val['category']);
        }

        return $ret;
    }

    /**
     * upravit paragon
     * @param type $chitId
     * @param ArrayAccess $val
     * @return type 
     */
    public function update($chitId, $val) {
        $changeAbleData = array("date", "recipient", "purpose", "price", "category");

        if (!is_array($val) && !($val instanceof ArrayAccess))
            throw new InvalidArgumentException("Values nejsou ve správném formátu");
        $chit = $this->get($chitId);

        if (isset($val['id']))
            $val['id'] = $chitId;

        $toChange = array();
        foreach ($changeAbleData as $name) {
            if (isset($val[$name])) {
                if ($name == 'price') {
                    $toChange['priceText'] = $val[$name];
                    $toChange[$name] = $this->solveString($val[$name]);
                    continue;
                }
                $toChange[$name] = $val[$name];
            }
        }
        $ret = $this->table->update($chitId, $toChange);
        //category update
        if ($chit->type == "camp") {
            $this->updateCategory($chit->actionId, $chit->category);
            if (isset($val["category"]))
                $this->updateCategory($chit->actionId, $val["category"]);
        }

        return $ret;
    }

    /**
     * smazat paragon
     * @param type $chitId
     * @param type $actionId
     * @return type 
     */
    public function delete($chitId, $actionId) {
        $chit = $this->get($chitId);
//        if ($chit->type != "general") //category update
//            $this->updateCategory($actionId, $chit->category);
        return $this->table->delete($chitId, $actionId);
    }

    /**
     * smazat všechny paragony dané akce
     * použití při rušení celé akce
     * @param type $actionId
     * @return type 
     */
    public function deleteAll($actionId) {
        return $this->table->deleteAll($actionId);
    }

    /**
     * vrací všechny kategorie akcí
     * @param bool $all - vracet vsechny informace o kategoriích?
     * @return array
     */
    public function getCategories($all = FALSE) {
        if ($all)
            return $this->table->getCategoriesAll();
        return $this->table->getCategories();
    }

    /**
     * vrací prijmové kategorie výprav
     * @return array 
     */
    public function getCategoriesIn() {
        return $this->table->getCategories("in");
    }

    /**
     * vrací výdajové kategorie výprav
     * @return array 
     */
    public function getCategoriesOut() {
        return $this->table->getCategories("out");
    }

    /*     * ******** CAMP CATEGORIES *********** */

    /**
     * seznam všech kategorií ze skautISu
     * @param type $actionId
     * @param bool $isEstimate
     * @return array(ID=>category, ...)
     */
    public function getCategoriesCamp($actionId, $isEstimate = false) {
        $tmp = $this->skautIS->event->EventCampStatementAll(array("ID_EventCamp" => $actionId, "IsEstimate" => $isEstimate));
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
     * @param type $actionId
     * @return array(in=>(id, ...), out=>(...))
     */
    public function getCategoriesCampPairs($actionId) {
        $in = $out = array();

        $cacheId = __FUNCTION__ . "_" . $actionId;
        if (!($all = $this->load($cacheId))) {
            foreach ($this->getCategoriesCamp($actionId, false) as $i) {
                if ($i->IsRevenue) {//výnosy?
                    $in[$i->ID] = $i->EventCampStatementType;
                } else {
                    $out[$i->ID] = $i->EventCampStatementType;
                }
            }
            $all = array("in" => $in, "out" => $out);
            $this->save($cacheId, $all);
        }
        return $all;
    }

    /**
     * vrací soucet v kazdé kategorii
     * @param type $actionId
     * @return (ID=>SUM)
     */
    public function getCategoriesCampSum($actionId) {
        $db = $this->table->getTotalInCategories($actionId);
        $all = $this->getCategoriesCamp($actionId, false);
        foreach ($all as $key => $item) {
            $all[$key] = array_key_exists($key, $db) ? $db[$key] : 0;
        }
        return $all;
    }

    /**
     * upraví celkový součet dané kategorie ve skautISu podle zadaných paragonů nebo podle parametru $ammout
     * @param int $aid
     * @param int $categoryId 
     * @param float $ammout 
     */
    public function updateCategory($aid, $categoryId, $ammout = NULL) {
        if ($ammout === NULL)
            $ammout = (int) $this->table->getTotalInCategory($categoryId);
        $this->skautIS->event->EventCampStatementUpdate(array(
            "ID" => $categoryId,
            "ID_EventCamp" => $aid,
            "Ammount" => $ammout,
            "IsEstimate" => false
                ), "eventCampStatement");
    }

    /**
     * ověřuje konzistentnost dat mezi paragony a SkautISem
     * @param type $actionId
     * @return boolean 
     */
    public function isConsistent($actionId, $repair = false, &$toRepair = NULL) {
        $sumSkautIS = $this->getCategoriesCamp($actionId, false);
        //$toRepair = array();
        foreach ($this->getCategoriesCampSum($actionId) as $catId => $ammount) {
            if ($ammount != $sumSkautIS[$catId]->Ammount) {
                if ($repair) //má se kategorie oprazvit?
                    $this->updateCategory($actionId, $catId, $ammount);
                else
                    $toRepair[$catId] = $ammount; //seznam ID vadných kategorií a jejich částek
            }
        }
        return empty($toRepair) ? true : false;
    }

    /*     * ******** END CAMP CATEGORIES *********** */

    /**
     * je akce celkově v záporu?
     * @param type $actionId
     * @return bool
     */
    public function isInMinus($actionId) {
        $data = $this->table->isInMinus($actionId);
        return @(($data["in"] - $data["out"]) < 0) ? true : false; //@ potlačuje chyby u neexistujicich indexů "in" a "out"
    }

    /**
     * vrací PDF s vybranými paragony
     * @param type $unitService
     * @param type $template
     * @param type $actionInfo
     * @param type $chits
     * @param type $fileName 
     */
    public function printChits($unitService, $template, $actionInfo, $chits, $fileName) {
        $income = array();
        $outcome = array();
        foreach ($chits as $c) {
            if ($c->ctype == "in") {
                $income[] = $c;
                continue;
            }
            $outcome[] = $c;
        }

        $template->registerHelper('priceToString', 'AccountancyHelpers::priceToString');
        $template->setFile(dirname(__FILE__) . '/ex.chits.latte');
        $template->income = $income;
        $template->outcome = $outcome;
        $template->oficialName = $unitService->getOficialName($actionInfo->ID_Unit);
        $this->makePdf($template, $fileName . ".pdf");
    }

    /**
     * vyhodnotí řetězec obsahující čísla, +, *
     * @param string $str - výraz k výpčtu
     * @return int 
     */
    function solveString($str) {
        $str = str_replace(",", ".", $str); //prevede desetinou carku na tecku
        preg_match_all('/(?P<cislo>[0-9]+[.]?[0-9]{0,2})(?P<operace>[\+\*]+)?/', $str, $matches);
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