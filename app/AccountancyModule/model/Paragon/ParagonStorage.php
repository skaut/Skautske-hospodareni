<?php

class ParagonStorage extends BaseStorage {

    private $list;

    function __construct() {
        parent::__construct();
        $this->list = array();
    }

    /**
     * vrací počet paragonů
     * @return int
     */
    public function getCount() {
        return count($this->list);
    }

    /**
     * vraci seznam paragonů, lze omezit pouze na výdaje
     * @param bool $pouzeVydaje omezit na výdaje?
     * @return array(Paragon)
     */
    function getAll($pouzeVydaje = false) {
        if (!$pouzeVydaje) {
            return $this->list;
        } else {
            $ret = array();
            $types = $this->getParagonCategoriesIn();
            foreach ($this->list as $p) {
                if($p->type != "pp"){
                    $ret[] = $p;
                }
//                if ($this->isParagonOut($p))
//                    $ret[] = $p;
            }
            return $ret;
        }
    }

    /**
     * vraci paragon podle ID
     * @param int $key
     * @return Paragon
     */
    public function get($key){
        if (isset($this->list[$key]))
            return $this->list[$key];
        return FALSE;
    }

    /**
     * nastavuje konkretní Paragon na novou hodnotu
     * @param  int - key in array
     * @param  Paragon
     * @return bool
     * @throws InvalidArgumentException
     */
    function set($key, $value) {
        if (!($value instanceof Paragon))
            throw new InvalidArgumentException("Value must be instance of Paragon, " . gettype($value) . " given");
        $this->list[$key] = $value;
        $this->sortParagons();
        return true;
    }

    /**
     * přidat paragon
     * @param Paragon $value
     * @return array(Paragon)
     */
    function add(Paragon $value) {
        $ret = $this->list[] = $value;
        $this->sortParagons();
        return $ret;
    }

    /**
     * odebrat paragon podle ID
     * @param int ParagonId
     * @return bool
     */
    function remove($key) {
        unset($this->list[$key]);
        return TRUE;
    }

    /**
     * smaze vsechny paragony
     * @return <type>
     */
    function clearList(){
        $this->list = array();
        return true;
    }

    /**
     * vraci pole categorii s částkami
     * @return <array>
     */
    function getCategoriesPrice() {
        $arr = array_fill_keys(array_keys($this->getParagonCategoriesAll()), 0); //array of categories
        foreach ($this->list as $p) {
            $arr[$p->type] += $p->price;
        }
        return $arr;
    }

    /**
     * @return bool
     */
    function isInMinus() {
        $total = 0;
        return FALSE;
        foreach ($this->list as $p) {
            if (array_key_exists($p->type, $this->getParagonCategoriesIn()))
                $total += $p->price;
            else
                $total -= $p->price;
            if ($total < 0)
                return true;
        }
        return false;
    }

    /**
     * seřadit paragony podle data a podle typu
     */
    // <editor-fold defaultstate="collapsed" desc="sortParagons">
    function sortParagons() {
        function cmp(Paragon $a, Paragon $b) {
            $adate = $a->date;
            $bdate = $b->date;
            if ($adate == $bdate) {
                if ($a->type == 'pp')
                    return -1;
                if ($b->type == 'pp')
                    return 1;
                return 0;
            }
            return ($adate < $bdate) ? -1 : 1;
        }
        uasort($this->list, 'cmp');
    }
    // </editor-fold>

    /**
     * @param Paragon $p
     * @return bool
     */
    protected function isParagonIn(Paragon $p){
        return in_array($p->type, $this->getParagonCategoriesIn());
    }

    /**
     * @param Paragon $p
     * @return bool
     */
    protected function isParagonOut(Paragon $p){
        return array_key_exists($p->type, $this->getParagonCategoriesOut());
    }


}
