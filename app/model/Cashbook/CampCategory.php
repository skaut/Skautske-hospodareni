<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Money\Money;

use function mb_substr;

final class CampCategory implements ICategory
{
    public function __construct(private int $id, private Operation $operationType, private string $name, private Money $total, private ParticipantType|null $participantType = null)
    {
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

    public function getTotal(): Money
    {
        return $this->total;
    }

    public function getParticipantType(): ParticipantType|null
    {
        return $this->participantType;
    }

    public function isVirtual(): bool
    {
        return false;
    }
}
