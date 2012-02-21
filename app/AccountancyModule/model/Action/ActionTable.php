<?php

/**
 * @author sinacek
 */

class ActionTable extends BaseTable {

    public function getReceipts($actionId){
        dibi::fetchAll("SELECT * FROM [" . self::TABLE_RECEIPTS . "] WHERE userId=%i", $uId, " %if", !$deleted, " AND deleted=0 %end");
    }


//    /**
//     * akce vybraneho uzivatele
//     * @param int $uId
//     * @param int $deleted[0,1]
//     * @return array(DibiRow)
//     */
//    function getByUser($uId, $deleted) {
//        return ArrayHash::from(dibi::fetchAll("SELECT * FROM [" . self::TABLE_USER_ACTION_VIEW . "] WHERE userId=%i", $uId, " %if", !$deleted, " AND deleted=0 %end"), TRUE);
//    }
//
//    /**
//     *
//     * @param int $deleted[0,1]
//     * @return array(DibiRow)
//     */
//    function getAll($deleted){
//        return ArrayHash::from(dibi::fetchAll("SELECT * FROM [" . self::TABLE_VYPRAVYVIEW . "] WHERE 1 %if", !$deleted, " AND deleted=0 %end"), TRUE);
//    }
//
//
//    /**
//     * vlozi novou akci do db a vrací její ID
//     * @param string $name
//     * @param text    $serAkce
//     * @param text      $serUcastnici
//     * @param text      $serParagony
//     * @return int      id akce
//     */
//    function add($name, $serAkce, $serUcastnici, $serPragony) {
//        dibi::query("INSERT INTO [" . self::TABLE_VYPRAVY . "] (`name`, `akce`, `ucastnici`, `paragony`) VALUES (%s, %s, %s, %s)", $name, $serAkce, $serUcastnici, $serPragony);
//        return dibi::getInsertId();
//    }
//
//    /**
//     * upraví akci v databázi
//     * @param int $akceId
//     * @param string $name - název výpravy
//     * @param string $serContent - serializovaný objekt výprava
//     * @return int počet upravených řádků
//     */
//    function update($akceId, $name, $serAkce, $serUcastnici, $serPragony) {
//        return dibi::query("UPDATE [" . self::TABLE_VYPRAVY . "] SET `name`=%s, `akce`=%s, `ucastnici`=%s, `paragony`=%s ", $name, $serAkce, $serUcastnici, $serPragony, " WHERE deleted=0 AND id=%i", $akceId);
//    }
//
//    /**
//     * smaze akci
//     * @param <type> $id
//     * @return <type>
//     */
//    function delete($id) {
//        return dibi::query("UPDATE [" . self::TABLE_VYPRAVY . "] SET `deleted` = '1' WHERE id=%s AND `deleted` = 0 ", $id);
//    }
//
//    function addAccess($uId, $akceId){
//        return dibi::query("INSERT INTO [" . self::TABLE_VYPRAVYUSERS . "] (`userId`,`vypravaId`) VALUES (%i, %i)", $uId, $akceId);
//    }
//
//    function get($id) {
//        return ArrayHash::from(dibi::fetch("SELECT * FROM [" . self::TABLE_VYPRAVY . "] WHERE id=%i LIMIT 1", $id));
//    }
//
//    /**
//     * zamkne akci v db
//     * kontroluje práva
//     * @param type $akceId
//     * @param type $userId
//     * @return int 
//     */
//    //function lock($akceId, $userId){
//    //    return dibi::query("UPDATE [" . self::TABLE_VYPRAVYVIEW . "] SET `lock` = %i,", $userId, " lockTime=NOW() WHERE vypravaId=%i", $akceId," AND (`lock` is NULL OR `lock` =%i ",$userId ,") AND `deleted` = 0 ");
//    //}
//    
//    /**
//     * odemkne pouze kdyz lock == $userId
//     * kontroluje práva
//     * @param int $akceId
//     * @param int $userId
//     * @return  int
//     */
////    function unlock($akceId, $userId){
////        return dibi::query("UPDATE [" . self::TABLE_VYPRAVY . "] SET `lock` = NULL, lockTime= NULL WHERE id=%i", $akceId," AND `lock` =%i ", $userId," AND `deleted` = 0");
////    }
//
//    function isAccess($userId, $akceId) {
//        return dibi::fetchSingle("SELECT * FROM [" . self::TABLE_VYPRAVYUSERS . "] WHERE userId=%i", $userId, " AND vypravaId=%i", $akceId);
//    }

}