<?php

class UcastnikStorage extends Accountancy_BaseStorage {

    /**
     * Jméno kdo přijal peníze
     * @var string
     */
    private $prijal;
    /**
     * jméno pokladníka
     * @var string
     */
    private $pokladnik;
    /**
     * Datum hromadného příjmového dokladu
     * @var Date
     */
    private $date;

    /**
     *
     * @var array()
     */
    private $ucatnici;

    function __construct() {
        parent::__construct();
        $this->ucatnici = array();
    }

    // <editor-fold defaultstate="collapsed" desc="getters & setters">
    function getPrijal() {
        return $this->prijal;
    }

    function setPrijal($s) {
        $this->prijal = $s;
    }

    function getPokladnik() {
        return $this->pokladnik;
    }

    function setPokladnik($s) {
        $this->pokladnik = $s;
    }

    function getDate() {
        return $this->date;
    }

    function setDate($s) {
        $this->date = $s;
    }
    // </editor-fold>

    public function add(MU $u) {
        return $this->ucatnici[$u->u] = $u;
    }

    /**
     * vraci účastnika podle ID
     * @param int $key
     * @return MU
     */
    public function get($key) {
        return $this->ucatnici[$key];
    }

    /**
     * upraví vlastnost Ucastnika podle klíče
     * @param int $key
     * @param string $property
     * @param mixed $value
     */
    public function updateUcastnik($key, $property, $value) {
        $this->ucatnici[$key]->$property = $value;
    }

    /**
     * smaže účastníka
     * @param int $key
     */
    public function removeUcastnik($key) {
        unset($this->ucatnici[$key]);
    }

    /**
     * vrací pole účastníků
     * @return array(MU)
     */
    public function getAll() {
        return is_array($this->ucatnici) ? $this->ucatnici : array();
    }

    /**
     * vymaže seznam účastníků
     */
    public function clear() {
        $this->ucatnici = array();
    }

    /**
     * vraci pole username => realname ze session se seznamem ucastniku akce
     */
    public function getList() {
        $p = array();
        foreach ($this->ucatnici as $key => $value) {
            $p[$key] = $value->r;
        }
        return $p;
    }

    /**
     * vraci pocet ucastniku ve veku mensim nez $limit k času(datu) $time
     * @param string $limit  < věk účastníků - zadaná v rocích
     * @param string $time ve formátu timestamp, k jakému datu se počítá věk
     * @return int
     */
    public function getCount($limit = null, $time = null) {
        if ($limit === NULL)
            return count($this->ucatnici);
        if ($time === NULL)
            $time = time();

        //mezní hodnota dnešní datum minus 26 let
        $mezniHodnota = strtotime('-26 year', $time);

        $ret = 0;
        $umodel = new Ucetnictvi_UserService();
        if (($ucastnici = $umodel->getByIDs(array_keys($this->getAll()))) && !empty($ucastnici)) {
            foreach ($ucastnici as $u) {
                if (strtotime($u->birthday) > $mezniHodnota) {
                    $ret++;
                }
            }
        }
        return $ret;
    }

}
