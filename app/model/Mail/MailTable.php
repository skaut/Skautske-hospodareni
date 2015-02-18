<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class MailTable extends BaseTable {

    public function get($id) {
        return $this->connection->fetch("SELECT * FROM [" . self::TABLE_PA_SMTP . "] WHERE id=%i", $id);
    }

    public function getAll($unitId) {
        return $this->connection->fetchAll("SELECT id, host, username, secure, created FROM [" . self::TABLE_PA_SMTP . "] WHERE unitId=%i", $unitId);
    }

    public function getPairs($unitId) {
        return $this->connection->fetchPairs("SELECT id, username FROM [" . self::TABLE_PA_SMTP . "] WHERE unitId=%i", $unitId);
    }

    public function getSmtpByGroup($groupId) {
        return $this->connection->fetch("SELECT s.* FROM [" . self::TABLE_PA_GROUP_SMTP . "] gs INNER JOIN [" . self::TABLE_PA_SMTP . "] s ON (gs.smtpId = s.id) WHERE gs.groupId=%i", $groupId);
    }

    public function addSmtp($unitId, $host, $username, $password, $secure) {
        return $this->connection->insert(self::TABLE_PA_SMTP, array(
                    "unitId" => $unitId,
                    "host" => $host,
                    "username" => $username,
                    "password" => $password,
                    "secure" => $secure,
                    "created" => array("%sql" => "NOW()")
                ))->execute();
    }

    public function removeSmtp($unitId, $id) {
        return $this->connection->query("DELETE FROM [" . self::TABLE_PA_SMTP . "] WHERE unitId=%i", $unitId, " AND id=%i", $id, " LIMIT 1");
    }

//    public function updateSmtp($unitId, $id, $data) {
//        return $this->connection->update(self::TABLE_PA_SMTP, $data)->where("unitId=%i", $unitId, " AND id=%i", $id)->execute();
//    }

    public function addSmtpGroup($groupId, $smtpId) {
        return $this->connection->query("INSERT INTO [" . self::TABLE_PA_GROUP_SMTP . "] (groupId, smtpId) VALUES (%i, ", $groupId, "  %i)", $smtpId, " ON DUPLICATE KEY UPDATE smtpId=%i", $smtpId);
    }

}
