<?php

namespace Model\Payment\Commands;

use Model\Payment\MailCredentials\MailProtocol;

final class CreateMailCredentials
{

    /** @var int */
    private $unitId;

    /** @var string */
    private $host;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var MailProtocol */
    private $protocol;

    /** @var string */
    private $sender;

    /** @var int */
    private $userId;

    public function __construct(
        int $unitId, string $host, string $username, string $password, MailProtocol $protocol, string $sender, int $userId
    )
    {
        $this->unitId = $unitId;
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->protocol = $protocol;
        $this->sender = $sender;
        $this->userId = $userId;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getProtocol(): MailProtocol
    {
        return $this->protocol;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

}
