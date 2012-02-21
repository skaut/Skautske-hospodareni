<?php

class BaseStorage extends Object {

    public $paragonCategoriesIn;
    public $paragonCategoriesOut;
    protected $oddily;

    function __construct() {
        $this->paragonCategoriesIn = array(
            "pp" => "Příjmový",
            "di" => "Dotace", //Dotace In => di
        );

        $this->paragonCategoriesOut = array(
            "t" => "Potraviny",
            "j" => "Jízdné",
            "n" => "Nájemné",
            "m" => "Materiál (Drobné vybavení)",
            "s" => "Služby",
            "pr" => "Převod do odd. pokladny",
            "do" => "Dotace",
            "un" => "Neurčeno",
        );

        $this->oddily = array(
            "vl" => "Vlčata",
            "sv" => "Světlušky",
            "si" => "Skauti",
            "sy" => "Skautky",
            "rr" => "R&R",
        );
    }

    public function getParagonCategoriesIn() {
        //dump($this->paragonCategoriesIn);
        return $this->paragonCategoriesIn;
    }

    public function getParagonCategoriesOut() {
        return $this->paragonCategoriesOut;
    }

    public function getOddily() {
        return $this->oddily;
    }

    public function getParagonCategoriesAll(){
        return array_merge($this->paragonCategoriesIn, $this->paragonCategoriesOut);
    }



}