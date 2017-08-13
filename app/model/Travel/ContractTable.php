<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 * správa smluv o proplácení cestovních náhrad u cestovních příkazů
 */
class ContractTable extends BaseTable
{

    public function add($values)
    {
        return $this->connection->query("INSERT INTO [" . self::TABLE_TC_CONTRACTS . "] ", $values);
    }

}
