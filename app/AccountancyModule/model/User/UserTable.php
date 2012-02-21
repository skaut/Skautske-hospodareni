<?php

/**
 * @author sinacek
 */

class UserTable extends BaseTable {

    /**
     * vraci uzivatele podle userID
     * @param int $userID
     * @return DibiRow
     */
    function get($userID) {
        return dibi::fetch("SELECT * FROM [" . self::TABLE_UC_USERS . "]  WHERE deleted = 0 AND id = %s LIMIT 1", $userID);
    }

    /**
     *
     * @param array of roles
     * @return dataSource of getAll
     */
    function getList($roles = array()) {
        $query = dibi::fetchAll("SELECT u.nick as nick, u.name as firstname, u.lastname as lastname, u.id as userID, ro.label as role FROM [" . self::TABLE_UC_USERS . "] as u
                        LEFT JOIN [" . self::TABLE_UC_ROLES . "] as ro
                        ON (u.role = ro.id)
                        where deleted = 0
                        %if ", !empty($roles), " AND ro.label in %in %end", $roles,
                        "ORDER BY role, firstname"
        );
        return $query;
    }

    /**
     * vraci pole $userID => $name
     * @param array($roles)
     * @return array()
     */
    function getPairs($roles) {
        $result = dibi::fetchAll("SELECT u.name, u.lastname, u.id as userID FROM [" . self::TABLE_UC_USERS . "] as u
                                    LEFT JOIN [" . self::TABLE_UC_ROLES . "] as ro
                                    ON (u.role = ro.id)
                                    WHERE deleted = 0 %if ", !empty($roles), " AND ro.label in %in ", $roles, " %end
                                    ORDER BY lastname, name");
        $ret = array();
        foreach ($result as $value) {
            $ret[$value->userID] = $value->lastname . " " . $value->name;
        }
        return $ret;
    }

    /**
     * vraci seznam uzivatelů podle ID
     * @param array $arr
     * @return array
     */
    function getByIDs($arr) {
        if (!is_array($arr))
            return false;
        $ret = dibi::fetchAll("SELECT * FROM [" . self::TABLE_UC_USERS . "] as u
                                WHERE u.deleted = 0 AND u.id in %in ", $arr, " ORDER BY u.role ASC, u.name");
        return $ret;
    }

    /**
     *
     * @return type 
     */
    public function getRoles(){
        return dibi::fetchPairs("SELECT id, label FROM [" . self::TABLE_UC_ROLES . "] ORDER BY orderby");
    }
    
    public function getUnits($userId){
        return dibi::fetchPairs("SELECT u.id, u.name FROM [" . self::TABLE_UNITSUSERS . "] as us LEFT JOIN [" . self::TABLE_UNITS . "] as u ON (us.unit = u.id) WHERE us.user = %i", $userId, " AND u.deleted = 0");
    }


    /**
     * aktualizuje uzivatele zadane v poli podle RC
     * @param <array> $users
     * @param array(role) $roles - asociativni pole typu vlce => 1
     * @return <int> - pocet aktualizovanych řádků
     */
    function updateUsers($users) {
        //dump($users);
        $affectedRows = 0;
        foreach ($users as $u) {
            unset ($arr);
            $arr["titul"] = $u->DegreeInFrontOf;
            $arr["name"] = $u->FirstName;
            $arr["lastName"] = $u->LastName;
            $arr["titul2"] = $u->DegreeBehind;
            $arr["nick"] = $u->NickName;
            $arr["birthday"] = substr($u->Birthday, 0, 10);
            $arr["street"] = $u->Street;
            $arr["town"] = $u->City;
            $arr["zip"] = $u->Postcode;
            $arr["stat"] = $u->State;
            $arr["email"] = isset ($u->Email) ? $u->Email : "";
            $arr["phone"] = isset ($u->Phone) ? $u->Phone : "";
            $arr["clenstvi"] = $u->ID_MembershipType;
            $arr["role"] = $u->ID_MembershipCategory;
            $arr["RC"] = $u->IdentificationCode;
            dibi::query("INSERT INTO [" . self::TABLE_UC_USERS_DEMO . "]", $arr, " ON DUPLICATE KEY UPDATE %a", $arr);
            $affectedRows += (dibi::affectedRows() > 0 ) ? 1 : 0;
        }
        return $affectedRows;
    }


}