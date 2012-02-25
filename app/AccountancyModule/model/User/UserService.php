<?php

/**
 * @author sinacek
 */

class UserService extends BaseService {
    
    public function __call($name, $arguments) {
        return 1;
    }
    
    public function findUser($name){
        return $this->skautIS->org->PersonAll(array("DisplayName"=>$name));
    }


//    private $SES_EXPIRATION = "+ 3 days";
//    /**
//     * @var Session
//     */
//    private $ses;
//    
//    function __construct() {
//        parent::__construct();
//        $this->table = new UserTable();
//        
//        $this->ses = Environment::getSession(__CLASS__);
//        $this->ses->setExpiration($this->SES_EXPIRATION);
//    }
//    
//    function getUnitId(){
//        return $this->ses->unitId;
//    }
//    function setUnitId($unitId){
//        $this->ses->unitId = $unitId;
//    }
//
//    /**
//     * vraci pole uzivatelů podle zadanych ID
//     * @param array $ids 
//     * @return array()
//     */
//    public function getByIDs($ids) {
//        return $this->table->getByIDs($ids);
//    }
//
//    /**
//     * vraci uzivatele podle userID
//     * @param int $userID
//     * @return DibiRow
//     */
//    function get($userID) {
//        return $this->table->get($userID);
//    }
//    
//    /**
//     * vraci seznam všech rolí
//     * @return array(roles)
//     */
//    function getRoles(){
//        return $this->table->getRoles();
//    }
//
//    /**
//     * vraci pole ucastniku s detaily
//     * @param array of roles
//     * @return dataSource of getAll
//     */
//    function getList($roles) {
//        if(!is_array($roles))
//            $roles = array($roles);
//        return $this->table->getList($roles);
//    }
//
//    /**
//     * vraci pole pro AutoComplete ve formátu $userID => $name
//     * @param array $roles
//     * @return array()
//     */
//    function getUsersToAC($roles = NULL) {
//        return array_values($this->getPairs($roles));
//    }
//
//    /**
//     * vraci pole $userID => $name
//     * @param array($roles)
//     * @return array()
//     * @todo predelat array("rover")...
//     */
//    function getPairs($roles = NULL) {
//        if ($roles == NULL)
//            $roles = array("rover", "ranger");
//        return $this->table->getPairs($roles);
//    }
//
//    /**
//     * aktualizuje ucastniky v db z pole
//     * @param array $users - pole ucastniku k aktualizaci
//     * @return <type>
//     */
//    function updateUsers($users, $unitId = NULL) {
//        if($unitId == NULL)
//            $unitId = $this->getUnitId();
//        if($users == NULL || empty ($users))
//            return -1;
//        //dump($users);
//        foreach ($users as $key => $value) { // pridava unitId k uzivatelům
//            $value["unit"] = $unitId;
//            $newUsers[$key] =  $value;
//        }
//        return $this->table->updateUsers($newUsers);
//    }
//    
//    /**
//     * vraci seznam unit pro přihlašeného uživatele
//     * @return array(ID => Name) 
//     */
//    function getMyUnits(){
//        $userId = Environment::getUser()->getIdentity()->data['id'];
//        return $this->table->getUnits($userId);
//    }
//
////    function importUsers(){
////        $data = dibi::fetchAll("SELECT users.username as username, users.password as password, users.role as role, users.hash as hash, importCSV.name as name, importCSV.lastname as lastname, importCSV.nick as nick, users.rc as rc, importCSV.street as street, importCSV.town as town, importCSV.zip as zip, importCSV.email as email, importCSV.phone as phone, users.deleted as deleted
////                                    FROM [users] as users RIGHT JOIN [importCSV] as importCSV
////                                    ON (users.rc = importCSV.rc)");
////
////        dibi::query("TRUNCATE TABLE `users` ");
////
////        $insert = "INSERT INTO ".self::TABLE." VALUES ";
////        foreach ($data as $key => $value) {
////            $insert .= "(";
////            foreach ($value as $key => $value) {
////                $insert .= "\"".$value."\", ";
////            }
////            $insert = substr($insert, 0, -2);
////            $insert .= "), ";
////
////        }
////        $insert = substr($insert, 0, -2).";";
////        dibi::query("%sql", $insert);
////    }
}