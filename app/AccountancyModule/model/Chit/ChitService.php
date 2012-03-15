<?php
/**
 * @author Hána František
 */
class ChitService extends BaseService {
    
    public function __construct() {
        parent::__construct();
        
        /** @var ChitTable */
        $this->table = new ChitTable();
    }
    
    public function get($id){
        return $this->table->get($id);
    }
    
    /**
     * seznam paragonů k akci
     * @param type $actionId
     * @return type 
     */
    public function getAll($actionId){
        return $this->table->getAll($actionId);
    }
    
    
    public function add($actionId, $v){
        if(!is_array($v) && !($v instanceof ArrayAccess))
            throw new InvalidArgumentException("Values nejsou ve správném formátu");
        //@todo kontrola jestli ma pravo přidávat k $actionId
        
        $values = array(
            "actionId" => $actionId,
            "date" => $v['date'],
            "recipient" => $v['recipient'],
            "purpose" => $v['purpose'],
            "price" => $v['price'],
            "priceText" => $v['priceText'],
            "category" => $v['type'],
        );
        
        return $this->table->add($values);
    }
    
    public function update($id, $v){
        if(!is_array($v) && !($v instanceof ArrayAccess))
            throw new InvalidArgumentException("Values nejsou ve správném formátu");
        //@todo kontrola jestli ma pravo editovat k $id
        
        $values = array(
            "date" => $v['date'],
            "recipient" => $v['recipient'],
            "purpose" => $v['purpose'],
            "price" => $v['price'],
            "priceText" => $v['priceText'],
            "category" => $v['type'],
        );
        return $this->table->update($id, $values);
    }
    
    public function delete($id, $actionId){
        //@todo kontrola jestli ma pravo mazat $actionId
        return $this->table->delete($id, $actionId);
    }
    
    /**
     * vrací prijmové kategorie
     * @return array 
     */
    public function getCategoriesIn(){
        return $this->table->getCategories("in");
    }
    
    /**
     * vrací výdajové kategorie
     * @return array 
     */
    public function getCategoriesOut(){
        return $this->table->getCategories("out");
    }
    
    /**
     * je akce v záporu?
     * @param type $actionId
     * @return bool
     */
    public function isInMinus($actionId){
        return $this->table->isInMinus($actionId);
    }
    
    
}