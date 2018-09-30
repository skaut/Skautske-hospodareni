<?php

declare(strict_types=1);

namespace Model\Payment\Group;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
final class BankAccount
{
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true, name="bank_account_id")
     */
    private $id;

    /**
     * @var DateTimeImmutable|NULL
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $lastPairing;

    private function __construct(int $id, ?DateTimeImmutable $lastPairing)
    {
        $this->id          = $id;
        $this->lastPairing = $lastPairing;
    }

    public static function create(int $bankAccountId) : self
    {
        return new self($bankAccountId, null);
    }

    public function updateLastPairing(DateTimeImmutable $lastPairing) : self
    {
        return new self($this->id, $lastPairing);
    }

    public function invalidateLastPairing() : self
    {
        return new self($this->id, null);
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getLastPairing() : ?DateTimeImmutable
    {
        return $this->lastPairing;
    }
}
