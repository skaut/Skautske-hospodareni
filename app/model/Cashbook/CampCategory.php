<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Money\Money;

final class CampCategory implements ICategory
{

    /** @var int */
    private $id;

    /** @var Operation */
    private $operationType;

    /** @var string */
    private $name;

    /** @var Money */
    private $amount;

    /** @var ParticipantType|NULL */
    private $participantType;

    public function __construct(int $id, Operation $operationType, string $name, Money $amount, ?ParticipantType $participantType)
    {
        $this->id = $id;
        $this->operationType = $operationType;
        $this->name = $name;
        $this->amount = $amount;
        $this->participantType = $participantType;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOperationType(): Operation
    {
        return $this->operationType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getShortcut(): string
    {
        return mb_substr($this->name, 0, 5, 'UTF-8');
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getParticipantType(): ?ParticipantType
    {
        return $this->participantType;
    }

}
