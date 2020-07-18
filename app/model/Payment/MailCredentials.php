<?php

declare(strict_types=1);

namespace Model\Payment;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Model\Payment\MailCredentials\MailProtocol;

/**
 * @ORM\Entity()
 * @ORM\Table(name="pa_smtp")
 */
class MailCredentials
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /** @ORM\Column(type="integer", name="unitId", options={"unsigned"=true}) */
    private int $unitId;

    /** @ORM\Column(type="string") */
    private string $host;

    /** @ORM\Column(type="string") */
    private string $username;

    /** @ORM\Column(type="string") */
    private string $password;

    /**
     * @ORM\Column(type="string_enum", name="secure", length=64)
     *
     * @Enum(class=MailProtocol::class)
     */
    private MailProtocol $protocol;

    /** @ORM\Column(type="string") */
    private string $sender;

    /** @ORM\Column(type="datetime_immutable", name="created") */
    private DateTimeImmutable $createdAt;

    public function __construct(
        int $unitId,
        string $host,
        string $username,
        string $password,
        MailProtocol $protocol,
        string $sender,
        DateTimeImmutable $createdAt
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

    public function getCreatedAt() : DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setPassword(string $password) : void
    {
        $this->password = $password;
    }
}
