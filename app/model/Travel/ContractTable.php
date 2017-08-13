<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 * správa smluv o proplácení cestovních náhrad u cestovních příkazů
 */
class ContractTable extends BaseTable
{

    public function get($id)
    {
        return $this->connection->fetch("SELECT * FROM [" . self::TABLE_TC_CONTRACTS . "] WHERE id=%i AND deleted=0", $id, " LIMIT 1");
    }

    public function add($values)
    {
        return $this->connection->query("INSERT INTO [" . self::TABLE_TC_CONTRACTS . "] ", $values);
    }

}
