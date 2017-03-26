<?php

namespace Model;

use Dibi\Connection;
use Dibi\Row;
use Model\Mail\MailerNotFoundException;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class MailTable extends BaseTable
{

    /**
     * MailTable constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    public function get($id): Row
    {
        $row = $this->connection->fetch("SELECT * FROM [" . self::TABLE_PA_SMTP . "] WHERE id=%i", $id);

        if($row === FALSE) {
            throw new MailerNotFoundException();
        }

        return $row;
    }

    /**
     * @param int[] $unitIds
     * @return \Dibi\Row[]
     */
    public function getAll(array $unitIds) : array
    {
        return $this->connection->select('id, host, username, secure, created, unitId')
            ->from(self::TABLE_PA_SMTP)
            ->where('unitId IN %in', $unitIds)
            ->fetchAll();
    }

    public function getPairs(array $unitIds) : array
    {
        return $this->connection->fetchPairs("SELECT id, username FROM [" . self::TABLE_PA_SMTP . "] WHERE unitId IN %in", $unitIds);
    }

    public function getSmtpByGroup($groupId)
    {
        return $this->connection->fetch("SELECT s.* FROM [" . self::TABLE_PA_GROUP_SMTP . "] gs INNER JOIN [" . self::TABLE_PA_SMTP . "] s ON (gs.smtpId = s.id) WHERE gs.groupId=%i", $groupId);
    }

    public function addSmtp($unitId, $host, $username, $password, $secure)
    {
        return $this->connection->insert(self::TABLE_PA_SMTP, [
            "unitId" => $unitId,
            "host" => $host,
            "username" => $username,
            "password" => $password,
            "secure" => $secure,
            "created" => ["%sql" => "NOW()"]
        ])->execute();
    }

    public function removeSmtp($unitId, $id)
    {
        return $this->connection->query("DELETE FROM [" . self::TABLE_PA_SMTP . "] WHERE unitId=%i", $unitId, " AND id=%i", $id, " LIMIT 1");
    }

}
