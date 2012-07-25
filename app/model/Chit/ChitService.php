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
                $i->cshort = substr($i->cshort, 0, 5);
                $list[$k] = $i;
            }
        }

        return $list;
    }

    public function getAllOut($actionId) {
        $data = $this->table->getAll($actionId);
        $res = array();
        foreach ($data as $i) {
            if ($i->ctype == "out")
                $res[] = $i;
        }
        return $res;
    }

    /**
     * vrací seznam příjmových dokladů
     * @param type $actionId
     * @return array 
     */
    public function getAllIncome($actionId) {
        $data = $this->table->getAll($actionId);
        $res = array();
        foreach ($data as $i) {
            if ($i->ctype == "in")
                $res[] = $i;
        }
        return $res;
    }

    public function getIn($actionId, $list) {
        return $this->table->getIn($actionId, (array) $list);
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
        $this->updateCategory($actionId, $val['category']);
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
        if ($chit->type != "general") //category update
            $this->updateCategory($actionId, $chit->category);
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
     * vrací všechny kategorie
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

    public function getCategoriesCamp($actionId) {
        $tmp = $this->skautIS->event->EventCampStatementAll(array("ID_EventCamp" => $actionId, "IsEstimate" => false));
        $res = array();
        foreach ($tmp as $i) {
            $res[$i->ID] = $i;
        }
        return $res;
    }

    /**
     * vrací seznam všech rozpočtových kategorií pro tábory
     * @param type $actionId
     * @return array(in=>(id, ...), out=>(...))
     */
    public function getCategoriesCampPairs($actionId) {
        $in = $out = array();

        $cacheId = __FUNCTION__ . "_" . $actionId;
        if (!($all = $this->load($cacheId))) {
            foreach ($this->getCategoriesCamp($actionId) as $i) {
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
    public function getTotalInCategories($actionId) {
        $db = $this->table->getTotalInCategories($actionId);
        $all = $this->getCategoriesCamp($actionId);
        foreach ($all as $key=> $item) {
            $all[$key] = array_key_exists($key, $db) ? $db[$key] : 0;
        }
        return $all;
    }

    /**
     * upraví celkový součet dané kategorie ve skautISu podle zadaných paragonů
     * @param type $aid
     * @param type $categoryId 
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
     * je akce celkově v záporu?
     * @param type $actionId
     * @return bool
     */
    public function isInMinus($actionId) {
        return $this->table->isInMinus($actionId);
    }

    public function printChits($context, $template, $actionInfo, $chits, $fileName) {
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
        $template->oficialName = $context->unitService->getOficialName($actionInfo->ID_Unit);
        $context->chitService->makePdf($template, $fileName . ".pdf");
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