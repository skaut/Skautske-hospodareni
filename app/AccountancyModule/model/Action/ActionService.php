<?php

/**
 * @author Hána František
 */
class ActionService extends BaseService {

    /**
     * vrací seznam paragonů k dané akci
     */
    public function getReceipts($actionId) {
        $p = new Paragon(array("price" => 20));
        return array($p);
    }

    public function getMyActions() {
        return $this->skautIS->event->EventGeneralAll();
    }

    public function get($id) {
        return $this->skautIS->event->EventGeneralDetail(array("ID" => $id));
    }

    public function getFunctions($id) {
        return $this->skautIS->event->EventFunctionAllGeneral(array("ID_EventGeneral" => $id));
    }

    /**
     * založí akci ve SkautIS
     * @param string $name nazev
     * @param date $start datum zacatku
     * @param date $end datum konce
     * @param ID_Person $leader
     * @param ID_Person $assistant
     * @param ID_Person $economist
     * @param ID_Unit $unit ID jednotky
     * @param int $scope  rozsah zaměření akce
     * @param int $type typ akce
     * @return int|stdClass ID akce 
     */
    public function create($name, $start, $end, $leader = NULL, $assistant = NULL, $economist = NULL, $unit = NULL, $scope = NULL, $type=NULL) {
        $scope = $scope !== NULL ? $scope : 2; //3-stedisko, 2-oddil
        $type = $type !== NULL ? $type : 2; //2-vyprava
        $unit = $unit !== NULL ? $unit : $this->skautIS->getUnitId();

        $ret = $this->skautIS->event->EventGeneralInsert(
                array(
            "ID" => 1, //musi byt neco nastavene
            "Location" => " ", //musi byt neco nastavene
            "Note" => " ", //musi byt neco nastavene
            "ID_EventGeneralScope" => $scope,
            "ID_EventGeneralType" => $type,
            "ID_Unit" => $unit,
            "DisplayName" => $name,
            "StartDate" => $start,
            "EndDate" => $end,
            "IsStatisticAutoComputed" => false,
                ), "eventGeneral");


        $this->skautIS->event->EventGeneralUpdateFunction(array(
            "ID" => $ret->ID,
            "ID_PersonLeader" => $leader,
            "ID_PersonAssistant" => $assistant,
            "ID_PersonEconomist" => $economist
        ));

        if (isset($ret->ID))
            return $ret->ID;
        return $ret;
    }

    /**
     * aktualizuje informace o akci
     * @param array $data
     * @return int
     */
    public function update($data) {
        $id = $data['aid'];
        $old = $this->get($id);

        $ret = $this->skautIS->event->EventGeneralUpdate(array(
            "ID"        => $id,
            "Location"  => $old->Location,
            "Note"      => $old->Note,
            "ID_EventGeneralScope"  => $old->ID_EventGeneralScope,
            "ID_EventGeneralType"   => $old->ID_EventGeneralType,
            "ID_Unit"               => $old->ID_Unit,
            "DisplayName"   => $data['name'],
            "StartDate"     => $data['start'],
            "EndDate"       => $data['end'],
                ), "eventGeneral");

        $this->skautIS->event->EventGeneralUpdateFunction(array(
            "ID" => $id,
            "ID_PersonLeader" => $data['leader'],
            "ID_PersonAssistant" => $data['assistant'],
            "ID_PersonEconomist" => $data['economist'],
        ));

        if (isset($ret->ID))
            return $ret->ID;
        return $ret;
    }

    /**
     * zrusit akci
     * @param int $id
     * @param string $msg
     * @return type 
     */
    public function cancel($id, $msg = NULL) {
        $msg = $msg ? $msg : " ";
        return $this->skautIS->event->EventGeneralUpdateCancel(
                        array(
                    "ID" => $id,
                    "CancelDecision" => $msg
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

