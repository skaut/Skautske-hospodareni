<?php
/**
 * @author Hána František
 */
class ChitService extends BaseService {
    
    public function __construct($skautIS = NULL) {
        parent::__construct($skautIS);
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
    public function getAll($actionId){;
        return $this->table->getAll($actionId);
    }
    
    public function getAllOut($actionId){
        $data = $this->table->getAll($actionId);
        $res = array();
        foreach ($data as $i){
            if($i->ctype == "out")
                $res[] = $i;
        }
        return $res;
    }
    
    /**
     * vrací seznam příjmových dokladů
     * @param type $actionId
     * @return array 
     */
    public function getAllIncome($actionId){
        $data = $this->table->getAll($actionId);
        $res = array();
        foreach ($data as $i){
            if($i->ctype == "in")
                $res[] = $i;
        }
        return $res;
    }
    
    public function getIn($actionId, $list){
        return $this->table->getIn($actionId, (array)$list);
    }

        /**
     * přidat paragon
     * @param type $actionId
     * @param array|ArrayAccess $val - údaje
     * @return type 
     */
    public function add($actionId, $val){
        
        if(!is_array($val) && !($val instanceof ArrayAccess))
            throw new InvalidArgumentException("Values nejsou ve správném formátu");
        
        $values = array(
            "actionId" => $actionId,
            "date" => $val['date'],
            "recipient" => $val['recipient'],
            "purpose" => $val['purpose'],
            "price" => $val['price'],
            "priceText" => $val['priceText'],
            "category" => $val['type'],
        );
        
        return $this->table->add($values);
    }
    
    /**
     * upravit patagon
     * @param type $id
     * @param ArrayAccess $v
     * @return type 
     */
    public function update($id, $v){
        if(!is_array($v) && !($v instanceof ArrayAccess))
            throw new InvalidArgumentException("Values nejsou ve správném formátu");
        
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
    
    /**
     * smazat paragon
     * @param type $id
     * @param type $actionId
     * @return type 
     */
    public function delete($id, $actionId){
        return $this->table->delete($id, $actionId);
    }
    
    /**
     * smazat všechny paragony
     * @param type $actionId
     * @return type 
     */
    public function deleteAll($actionId){
        return $this->table->deleteAll($actionId);
    }

        
    /**
     * vrací všechny kategorie
     * @param bool $all - vracet vsechny informace o kategoriích?
     * @return array
     */
    public function getCategories($all=FALSE){
        if($all)
            return $this->table->getCategoriesAll();
        return $this->table->getCategories();
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
     * je akce celkově v záporu?
     * @param type $actionId
     * @return bool
     */
    public function isInMinus($actionId){
        return $this->table->isInMinus($actionId);
    }
    
    public function printChits($context, $template, $actionInfo, $chits, $fileName){
        $income = array();
        $outcome = array();
        foreach ($chits as $c) {
            if($c->ctype == "in"){
                $income[] = $c;
                continue;
            }
            $outcome[] = $c;
        }
        
        $template->registerHelper('priceToString', 'AccountancyHelpers::priceToString');
        $template->setFile(dirname(__FILE__) . '/ex.chits.latte');
        $template->income = $income;
        $template->outcome = $outcome;
        $template->oficialName = $context->unitService->getOficialName($actionInfo->ID_Unit);
        $context->chitService->makePdf($template, $fileName.".pdf");
    }
    
}