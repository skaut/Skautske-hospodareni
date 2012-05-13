<?php

require_once 'PHPUnit/Autoload.php';

class ChitService_Test extends PHPUnit_Framework_TestCase {

    protected $service;
    const ACTION_ID = 1;

    public function testProcess() {

        $this->service = new ChitService();
        
        $this->assertTrue($this->addOk($idOk));
        $this->assertTrue($this->invalidData());
        $this->assertTrue($this->invalidID());
        $this->assertTrue($this->invalidCategory());
        $this->assertTrue($this->get($idOk));
        $this->assertEquals(43, $this->updatePrice($idOk, "22+7*3"));
        $this->assertTrue($this->delete($idOk));
    }

    public function addOk(&$idOk) {
        //platné přidání paragonu
        $chitOk = array(
            "actionId" => 22,
            "date" => date("c"),
            "recipient" => "unitTest",
            "purpose" => "unitTest",
            "price" => "123",
            "type" => 's',
            "category" => 's',
        );
        $result = true;
        try{
        $idOk = $this->service->add(self::ACTION_ID, $chitOk);
        } catch (DibiException $e){
            $result = false;
            $idOk = NULL;
        }
        return $result;
    }

    public function invalidData() {
        $result = false;
        try {
            $this->service->add(self::ACTION_ID, "test");
        } catch (Exception $exc) {
            $result = true;
        }
        return $result;
    }

    public function invalidID() {
        $result = false;
        try {
            $this->service->add(NULL, "test");
        } catch (Exception $exc) {
            $result = true;
        }
        return $result;
    }

    public function invalidCategory() {
        $result = false;
        try {
            $chitFailCategory = array("date" => date("c"), "recipient" => "unitTest", "purpose" => "testUnit", "price" => 234, "priceText" => 234, "type" => "idkfa",);
            $this->assertTrue($this->service->add(self::ACTION_ID, $chitFailCategory));
        } catch (DibiDriverException $exc) {
            $result = true;
        }
        return $result;
    }
    
    public function get($id){
        $p = $this->service->get($id);
        return (isset($p->id) && $p->id == $id) ? true : false;
    }
    
    public function updatePrice($id, $price){
        $values = array(
            "price" => $price,
        );
        $this->service->update($id, $values);
        $p = $this->service->get($id);
        return $p->price;
    }
    
    public function delete($id){
        return $this->service->delete($id, self::ACTION_ID) == 1 ? TRUE : FALSE;
    }
    
//    public function (){
//        
//        return $result;
//    }

    

}