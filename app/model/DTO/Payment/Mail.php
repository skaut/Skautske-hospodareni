<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Nette\SmartObject;

/**
 * @property-read int       $id
 * @property-read int       $unitId
 * @property-read string    $username
 * @property-read string    $host
 * @property-read string    $secure
 */
class Mail
{
    use SmartObject;

    private int $id;

    private int $unitId;

    private string $username;

    private string $host;

    private string $secure;

    private string $sender;

    public function __construct(int $id, int $unitId, string $username, string $host, string $secure, string $sender)
    {
        $this->id       = $id;
        $this->unitId   = $unitId;
        $this->username = $username;
        $this->host     = $host;
        $this->secure   = $secure;
        $this->sender   = $sender;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getUsername() : string
    {
        return $this->username;
    }

    public function getHost() : string
    {
        return $this->host;
    }

    public function getSecure() : string
    {
        return $this->secure;
    }

    public function getSender() : string
    {
        return $this->sender;
    }
}
