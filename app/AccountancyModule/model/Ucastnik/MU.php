<?php

/**
 * @author sinacek
 * MU = Mini Ucastnik
 */
class MU extends Object {

    public $u; //userID
    public $m; //money
    public $n; //nickname
    public $r; //realname

    function  __construct(array $values) {
        $this->u = isset($values['id']) ? $values['id'] : "";
        $this->n = isset($values['nick']) ? $values['nick'] : "";
        $this->r = isset($values['name']) || isset ($values['lastName']) ? $values['name']." ".$values['lastName'] : "";
        $this->m = isset($values['money']) ? $values['money'] : "";
        
    }
}

