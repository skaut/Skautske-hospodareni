<?php
//
///**
// * @author Hána František
// */
//class SkautisService {
//
//    protected $skautIS;
//    private static $instance;
//
//    /**
//     * Singleton
//     */
//    private function __construct() {
//        $context = Environment::getContext();
//        $this->skautIS = SkautIS::getInstance($context->parameters['skautisid']);
//        $this->skautIS->setTestMode($context->parameters['skautisTestMode']);
//    }
//
//    /**
//     * @return SkautisService
//     */
//    public static function getInstance() {
//        if (!(self::$instance instanceof self)) {
//            self::$instance = new self;
//        }
//        return self::$instance;
//    }
//
//    public function __call($name, $arguments) { //vola zakladni funkce třídy SkautIS        
//        if (count($arguments) == 1)
//            $arguments = $arguments[0];
//        return $this->skautIS->$name($arguments);
//    }
//
//    public function __get($name) {
//        return $this->skautIS->$name;
//    }
//
//}