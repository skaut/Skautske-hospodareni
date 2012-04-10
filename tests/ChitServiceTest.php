<?php

require_once 'PHPUnit/Framework.php';

class ChitServiceTest extends PHPUnit_Framework_TestCase {
    
    const ACTION_ID = 1;

    public function testProcess() {
        $o = new ChitService;
        
        //platné přidání paragonu
        $chitOk = array("date" => date("c"), "recipient" => "unitTest", "purpose" => "testUnit", "price" => 234, "priceText" => 234, "type" => "pp",);
        $idOk = $this->assertTrue($o->add(self::ACTION_ID, $chitOk));
        
        //neplatná hodnota paragonu
        $result = false;
        try {
            $o->add(self::ACTION_ID, "test");
        } catch (Exception $exc) {
            $result = true;
        }
        $this->assertTrue($result);
        
        //neplatné ID
        $result = false;
        try {
            $o->add(NULL, "test");
        } catch (Exception $exc) {
            $result = true;
        }
        $this->assertTrue($result);
        
        //pridání s neplatnou kategorií
        $chitFailCategory = array("date" => date("c"), "recipient" => "unitTest", "purpose" => "testUnit", "price" => 234, "priceText" => 234, "type" => "idkfa",);
        $this->assertTrue($o->add(self::ACTION_ID, $chitFailCategory));
        
        //získání paragonu
        $p = $o->get($idOk);
        $result = isset($p->ID)? true : false;
        $this->assertTrue($result);
        
        /*
         * update
         * 
         * delete
         * 
         * get
         * 
         * getCategories 
         */
        
    }

}