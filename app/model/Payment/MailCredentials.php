<?php

declare(strict_types=1);

namespace Model\Payment;

use Model\Payment\MailCredentials\MailProtocol;

class MailCredentials
{
    /** @var int */
    private $id;

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

    /** @var \DateTimeImmutable */
    private $createdAt;

    public function __construct(
        int $unitId,
        string $host,
        string $username,
        string $password,
        MailProtocol $protocol,
        string $sender,
        \DateTimeImmutable $createdAt
    ) {
        $this->unitId    = $unitId;
        $this->host      = $host;
        $this->username  = $username;
        $this->password  = $password;
        $this->protocol  = $protocol;
        $this->sender    = $sender;
        $this->createdAt = $createdAt;
    }

    public function getId() : int
    {
        return $this->id;
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

    public function getCreatedAt() : \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
