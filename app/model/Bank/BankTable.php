<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BankTable extends BaseTable {

    public function setToken($unitId, $token) {
        return $this->connection->query("INSERT INTO [" . self::TABLE_PA_BANK . "]", array("unitId" => $unitId, "token" => $token), "
            ON DUPLICATE KEY UPDATE %a", array("token" => $token));
    }

    public function getToken($unitId) {
        return $this->connection->fetchSingle("SELECT token FROM [" . self::TABLE_PA_BANK . "] WHERE unitId=%i", $unitId);
    }

    public function removeToken($unitId) {
        return $this->connection->query("DELETE FROM [" . self::TABLE_PA_BANK . "] WHERE unitId=%i", $unitId, " LIMIT 1");
    }

}
