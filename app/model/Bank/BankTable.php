<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BankTable extends BaseTable
{

    public function setToken($unitId, $token, $daysback)
    {
        return $this->connection->query("INSERT INTO [" . self::TABLE_PA_BANK . "]", ["unitId" => $unitId, "token" => $token, "daysback" => $daysback], "
            ON DUPLICATE KEY UPDATE %a", ["token" => $token, "daysback" => $daysback]);
    }

    public function getInfo($unitId)
    {
        return $this->connection->fetch("SELECT daysback, token FROM [" . self::TABLE_PA_BANK . "] WHERE unitId=%i", $unitId);
    }

    public function removeToken($unitId)
    {
        return $this->connection->query("DELETE FROM [" . self::TABLE_PA_BANK . "] WHERE unitId=%i", $unitId, " LIMIT 1");
    }

}
