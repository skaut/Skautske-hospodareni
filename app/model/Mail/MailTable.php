<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class MailTable extends BaseTable {

    public function getSmtp($unitId) {
        return $this->connection->fetch("SELECT * FROM [" . self::TABLE_PA_SMTP . "] WHERE unitId in %in", $unitId);
    }

}
