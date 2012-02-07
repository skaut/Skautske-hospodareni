<?php

/**
 * @author sinacek
 *
 * předek všech akcí s kterými lze pracovat, nelze vytvořit její instanci
 */
class Action extends Object implements IAction {

    protected $id; // id is set, if akce was restore from database
    protected $name; //string
    protected $numOJ; //string
    protected $from; //string
    protected $to; //string
    protected $place; //string
    //protected $lock; //bool

    /**
     * vytvori z daneho pole novy objekt
     * @param <type> $arr
     * @return self
     */

    static function from($arr) {
        $obj = new self;
        foreach ($arr as $key => $value) {
            $obj->$key = $value;
        }
        return $obj;
    }

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

    public function toArray() {
        $ret = array();
        foreach ($this->getReflection()->getDefaultProperties() as $key => $value) {
            $ret[$key] = $this->$key;
        }
        return $ret;
    }

//    public function __sleep() {
//        $arr = array();
//        $prop = get_object_vars($this);
//        foreach ($prop as $key => $val) {
//            $arr[$key] = $val;
//        }
//        unset($arr['lock']);
//        return $arr;
//    }

}
