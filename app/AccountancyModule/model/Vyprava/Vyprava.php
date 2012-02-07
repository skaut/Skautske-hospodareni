<?php

/**
 * @author sinacek
 */

class Vyprava extends Action {

    protected $leader; //Ucastnik
    protected $zpracoval; //Ucastnik
    protected $oddily; //array of oddil(vl=>true,... )
    protected $parPrefix; //string - paragon prefix
    protected $dotace; //bool


    public function &__get($name) {
        if (property_exists(__CLASS__, $name))
            return $this->$name;
        return parent::__get($name);
    }

    public function __set($name, $value) {
        if (property_exists(__CLASS__, $name))
            return $this->$name = $value;
        return parent::__set($name, $value);
    }

}