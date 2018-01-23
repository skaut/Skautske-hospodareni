<?php

declare(strict_types=1);

namespace Model\Payment\Group;

use DateTimeImmutable;

final class BankAccount
{

    /** @var int */
    private $id;

    /** @var DateTimeImmutable|NULL */
    private $lastPairing;

    private function __construct(int $id, ?DateTimeImmutable $lastPairing)
    {
        $this->id = $id;
        $this->lastPairing = $lastPairing;
    }

    public static function create(int $bankAccountId): self
    {
        return new self($bankAccountId, NULL);
    }

    public function updateLastPairing(DateTimeImmutable $lastPairing): self
    {
        return new self($this->id, $lastPairing);
    }

    public function invalidateLastPairing(): self
    {
        return new self($this->id, NULL);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLastPairing(): ?DateTimeImmutable
    {
        return $this->lastPairing;
    }

}
