<?php
//class UserService extends /*Nette\Application\*/BaseModel {
//    const TABLE = "users";
//
//    function get($username = NULL) {
//        if(is_null($username))
//            return FALSE;
//        return dibi::fetch("SELECT u.username as username FROM [" . self::TABLE . "] as u WHERE u.deleted = 0 AND u.username = %s LIMIT 1", $username);
//    }
//    
//    function isExist($username){
//        return (bool) $this->get($username);
//    }
//
//    static function makePassword($password, $username) {
//        return sha1($password . $username);
//    }
//
//    function getPassword($username = NULL) {
//        if($username === NULL)
//            return FALSE;
//        return dibi::fetchSingle("SELECT password FROM [" . self::TABLE . "] WHERE deleted = 0 AND username=%s", $username);
//    }
//
//    /**
//     * seznam uživatelů ID=>Username
//     * @return type 
//     */
//    public function getAllPairs() {
//        return dibi::fetchPairs("SELECT id, username FROM [" . self::TABLE . "] WHERE deleted=0");
//    }
//
//    function getList($roles = array()) {
//        $query = dibi::dataSource("SELECT u.username as username, ro.label as role FROM [" . self::TABLE . "] as u
//                        LEFT JOIN [users_roles] as ro
//                        ON (u.role = ro.id)
//                        where deleted = 0
//                        %if ", !empty($roles), " AND ro.label in (%s) %end", $roles);
//
//
//        return $query->orderBy(array("role", "username"));
//    }
//    
//    /**
//     *
//     * @return type 
//     * @todo predelat aby vracel realnou hodnotu z db
//     */
//    function getBasicRole(){
//        return 15;
//    }
//    
//    /**
//     * prida registraci uzivatele, který se přihlašuje přes skautIS
//     * @param $username - unikatni username
//     * @param int $skautID - skautIS ID
//     * @param $email
//     * @param int $role 
//     */
//    function addSkautIS($username, $skautID, $email, $role = NULL){
//        if($role === NULL)
//            $role = $this->getBasicRole();
//        dibi::query("INSERT INTO [".self::TABLE."] %v", array(
//            "username"=>$username,
//            "skautID"=>$skautID,
//            "email"=>$email,
//            "role"=>$role,
//            ));
//    }
//
//
//}
