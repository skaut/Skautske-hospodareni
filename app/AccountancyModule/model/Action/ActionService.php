<?php

/**
 * @author sinacek
 */
class ActionService extends BaseService {
    
    
    /**
     * vrací seznam paragonů k dané akci
     */
    public function getReceipts($actionId){
        $p = new Paragon(array("price"=>20));
        return array($p);
    }
    
    public function getMyActions(){
        return $this->skautIS->event->EventGeneralAll();
    }
    
    public function getDetail($id){
        return $this->skautIS->event->EventGeneralDetail(array("ID"=>$id));
    }

        /**
     * založí akci ve SkautIS
     * @param type $name nazev
     * @param type $start datum zacatku
     * @param type $end datum konce
     * @param type $unit ID jednotky
     * @param type $scope  rozsah zaměření akce
     * @param type $type typ akce
     * @return int ID akce 
     */
    public function create($name, $start, $end, $unit = NULL, $scope = NULL, $type=NULL){
        $scope  = $scope !== NULL ? $scope : 2; //3-stedisko, 2-oddil
        $type   = $type  !== NULL ? $type : 2; //2-vyprava
        $unit   = $unit  !== NULL ? $unit : $this->skautIS->getUnitId(); 
        
        
        $ret = $this->skautIS->event->EventGeneralInsert(
                array(
                    "ID" => 1,
                    "Location" => " ",
                    "Note" => " ",
                    "ID_EventGeneralScope" => $scope,
                    "ID_EventGeneralType" => $type,
                    "ID_Unit" => $unit,
                    "DisplayName" => $name,
                    "StartDate" => $start,
                    "EndDate" => $end,
                    "IsStatisticAutoComputed" => false,
                ), "eventGeneral");
        return $ret;
    }
    
    public function cancel($id, $msg = NULL) {
        $msg = $msg ? $msg : " ";
        return $this->skautIS->event->EventGeneralUpdateCancel(
                array(
                    "ID"=>$id,
                    "CancelDecision"=>$msg
                ), "eventGeneral");
    }

//    protected $SES_EXPIRATION = "+ 7 days";
//
//    /**
//     * @var IAction
//     */
//    protected $action;
//
//    /**
//     * @var UcastnikStorage
//     */
//    protected $ucastnici;
//
//    function __construct() {
//        parent::__construct();
//        /**
//         * @var Accountancy_ActionTable
//         */
//        $this->table = new ActionTable();
//
//        $this->persistenceVars = array("ucastnici");
//        $ns = Environment::getSession(__CLASS__);
//        $ns->setExpiration($this->SES_EXPIRATION);
//        $this->loadVars($ns);
//
//
//        if (!($this->action instanceof IAction)) {
//            $this->action = new Vyprava();
//        }
//
//        if (!($this->ucastnici instanceof UcastnikStorage)) {
//            $this->ucastnici = new UcastnikStorage();
//        }
//    }
//
//    public function &getAction() {
//        return $this->action;
//    }
//
//    public function getActionId() {
//        return $this->action->id;
//    }
//
//    public function setActionId($akceId) {
//        $this->action->id = $akceId;
//    }
//
//    /**
//     * @return UcastnikStorage
//     */
//    public function &getUcastnici() {
//        return $this->ucastnici;
//    }
//
//    /**
//     * vraci akce ke kterym ma pristup vybrany uzivatel
//     * @param type $uId
//     * @param type $deleted
//     * @return array
//     */
//    public function getByUser($uId, $deleted = false) {
//        return $this->table->getByUser($uId, $deleted);
//    }
//
//    /**
//     * měla by se používat POUZE pro admina
//     * @param int $uId
//     * @param bool $deleted
//     * @deprecated pouze dočasně pro admina
//     */
//    public function getAll($deleted = false) {
//        $this->table->getAll($deleted);
//    }
//
//    public function clear() {
//        $this->action = NULL;
//        $this->ucastnici = NULL;
//        return true;
//    }
//
//    /**
//     *
//     * @param type $actionId
//     * @param type $userId
//     * @return bool
//     */
//    public function unlock($actionId = NULL, $userId=NULL) {
//        if ($actionId === NULL)
//            $actionId = $this->getActionId();
//        if ($userId === NULL)
//            $userId = $userId = $this->user->getIdentity()->data['id'];
////        if($this->table->unlock($this->getAkceId(), $userId)){
////            $this->lock = NULL;
////            return true;
////        }
//        return true;
//    }
//
//    public function save() {
//        $userId = $this->user->getIdentity()->data['id'];
//        $name = ($this->action->name != "") ? $this->action->name : "noname_" . date("j-n-Y");
//        $serAkce = serialize($this->action);
//        $serUcastnici = serialize($this->ucastnici);
//
//        $actionId = $this->getActionId();
//        //$this->akce->id = NULL;
//        //dump($akceId);
//        if ($actionId) { //akce je upravována
//            if (!$this->isAccess($actionId))
//                return "noaccess";
//            $ret = $this->table->update($actionId, $name, $serAkce, $serUcastnici);
//            return ($ret == 0) ? "noupdate" : "update";
//        } else {
//            $actionId = $this->table->add($name, $serAkce, $serUcastnici);
//            if ($actionId) {
//                $ret2 = $this->table->addAccess($userId, $actionId);
//                $this->setActionId($actionId);
//                //$this->lock($akceId, $userId);
//            }
//            return ($actionId && $ret2) ? "insert" : "noinsert";
//        }
//    }
//
//    /**
//     *
//     * @param type $id
//     * @return type 
//     */
//    public function delete($id) {
//        if (!$this->isAccess($id))
//            return false;
//        return $this->table->delete($id);
//    }
//
//    /**
//     * nacte akci jako aktualni a lze ji editovat
//     * pokud k akci nemá oprávnění tak vrací že nejde zamknout
//     * @param int $actionId - id akce
//     * @param string &$msg - pripojuje zpravu o akci
//     * @return bool
//     */
//    public function restore($actionId, $msg = NULL) {
//        $userId = $this->user->getIdentity()->data['id'];
//
//        //pokusi se zamknout akci, pokud se to nepovede ukonci proces
////        if (!$this->lock($akceId, $userId)) {
////            $msg = "lock";
////            return false;
////        }
////        uz resi $this->lock();
////        if (!$this->isAccess($akceId)) {
////            $msg = "access";
////            return false;
////        }
//
//        $akce = $this->table->get($actionId);
//
////        uz resi $this->lock();
////        if (!$akce) {
////            $msg = "noresult";
////            return false;
////        }
//
//        $this->action = unserialize($akce->akce);
//        $this->ucastnici = unserialize($akce->ucastnici);
//        $this->setActionId($actionId);
//        return true;
//    }
//
//    /**
//     * overuje pristup k dane akci
//     * @param type $akceId
//     * @param type $userId
//     * @return type bool
//     */
//    function isAccess($akceId, $userId = NULL) {
//        if ($userId === NULL)
//            $userId = $this->user->getIdentity()->data['id'];
//        $access = $this->table->isAccess($userId, $akceId);
//        return $access ? true : false;
//    }
//
//    /**
//     * zajistuje persistentni promenne
//     */
//    function __destruct() {
//        $ns = Environment::getSession(__CLASS__);
//        $this->saveVars($ns);
//    }

}

