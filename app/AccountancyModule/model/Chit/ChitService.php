<?php

class ChitService extends BaseService {
    
    public function __construct() {
        parent::__construct();
        /** @var ChitTable */
        $this->table = new ChitTable();
    }
    
    public function getAll($actionId){
        return $this->table->getAll($actionId);
    }
    
    
    public function add($actionId, $v){
        if(!is_array($v) && !($v instanceof Traversable))
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
    
    public function delete($id, $actionId){
        //@todo kontrola jestli ma pravo mazat $actionId
        return $this->table->delete($id, $actionId);
    }
    
    /**
     * vrací prijmové kategorie
     * @return type 
     */
    public function getCategoriesIn(){
        return $this->table->getCategories("in");
        
    }
    
    /**
     * vrací výdajové kategorie
     * @return type 
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