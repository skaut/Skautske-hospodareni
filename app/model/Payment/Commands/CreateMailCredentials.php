<?php

declare(strict_types=1);

namespace Model\Payment\Commands;

use Model\Payment\MailCredentials\MailProtocol;

final class CreateMailCredentials
{
    private int $unitId;

    private string $host;

    private string $username;

    private string $password;

    private MailProtocol $protocol;

    private string $sender;

    private int $userId;

    public function __construct(
        int $unitId,
        string $host,
        string $username,
        string $password,
        MailProtocol $protocol,
        string $sender,
        int $userId
    ) {
        $this->unitId   = $unitId;
        $this->host     = $host;
        $this->username = $username;
        $this->password = $password;
        $this->protocol = $protocol;
        $this->sender   = $sender;
        $this->userId   = $userId;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getHost() : string
    {
        return $this->host;
    }

    public function getUsername() : string
    {
        return $this->username;
    }

    public function getPassword() : string
    {
        return $this->password;
    }

    public function getProtocol() : MailProtocol
    {
        return $this->protocol;
    }

    public function getSender() : string
    {
        return $this->sender;
    }

    public function getUserId() : int
    {
        return $this->userId;
    }
}
