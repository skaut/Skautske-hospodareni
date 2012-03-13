<?php

/**
 * @deprecated
 */
class Paragon extends Object {

    public $date; //datum 
    public $komu; //vyplaceno komu
    public $ucel; //ucela vyplaty
    public $price; // castka na paragonu
    public $priceText; // vyraz na spocitani paragonu
    public $type;   //prijmovy, potraviny, atd
    public $cislo; // siclo v pokladni knize


    /**
     *
     * @param ArrayHash $values
     */
    function  __construct($values = array()) {
        if(isset($values['price'])) {
            $price = $values['price'];
            if(preg_match("/^[0-9]*\.[0-9]$/", $values['price']))
                $price .= "0";
        }

        $this->date = $values['date'];
        $this->komu = isset($values['komu']) ? $values['komu'] : "";
        $this->ucel = isset($values['ucel']) ? $values['ucel'] : "";
        $this->price = isset($price) ? $price : "0";
        $this->priceText = isset($values['priceText']) ? $values['priceText'] : $values['price'];
        $this->type = isset($values['type']) ? $values['type'] : "";
        $this->cislo = isset($values['cislo']) ? $values['cislo'] : "";
    }

}