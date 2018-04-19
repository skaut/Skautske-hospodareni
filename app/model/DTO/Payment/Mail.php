<?php

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

    /** @var int */
    private $id;

    /** @var int */
    private $unitId;

    /** @var string */
    private $username;

    /** @var string */
    private $host;

    /** @var string */
    private $secure;

    public function __construct(int $id, int $unitId, string $username, string $host, string $secure)
    {
        $this->id = $id;
        $this->unitId = $unitId;
        $this->username = $username;
        $this->host = $host;
        $this->secure = $secure;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getSecure(): string
    {
        return $this->secure;
    }

}
