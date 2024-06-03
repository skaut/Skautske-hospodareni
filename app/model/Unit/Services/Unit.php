<?php

declare(strict_types=1);

namespace Model\Unit;

use function array_key_last;
use function array_map;
use function array_merge;
use function explode;
use function in_array;
use function sprintf;

class Unit
{
    private const OFFICIAL_UNIT_TYPES = [
        'stredisko',
        'kraj',
        'okres',
        'ustredi',
        'zvlastniJednotka',
    ];

    /** @param Unit[]|null $children */
    public function __construct(
        private int $id,
        private string $sortName,
        private string $displayName,
        private string|null $ic = null,
        private string $street,
        private string $city,
        private string $postcode,
        private string $registrationNumber,
        private string $type,
        private int|null $parentId = null,
        private array|null $children = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSortName(): string
    {
        return $this->sortName;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getFullDisplayName(bool $nonBreakingSpace = false): string
    {
        if ($this->isOfficial()) {
            return sprintf('Junák - český skaut, %s, z.%ss.', $this->getDisplayName(), $nonBreakingSpace ? '&nbsp;' : ' ');
        }

        return '';
    }

    public function getFullDisplayNameWithAddress(bool $nonBreakingSpace = false): string
    {
        return $this->getFullDisplayName($nonBreakingSpace) . ', ' . $this->getAddress() . ', IČO:' . ($nonBreakingSpace ? '&nbsp;' : ' ') . $this->ic;
    }

    public function getAddress(): string
    {
        return $this->street . ', ' . $this->city . ', ' . $this->postcode;
    }

    public function getIc(): string|null
    {
        return $this->ic;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getPostcode(): string|null
    {
        return $this->postcode;
    }

    public function getParentId(): int|null
    {
        return $this->parentId;
    }

    public function isOfficial(): bool
    {
        return in_array($this->type, self::OFFICIAL_UNIT_TYPES, true);
    }

    public function getShortRegistrationNumber(): string
    {
        $splitNumber = explode('.', $this->registrationNumber);

        return $splitNumber[array_key_last($splitNumber)];
    }

    public function getRegistrationNumber(): string
    {
        return $this->registrationNumber;
    }

    /** @return Unit[]|null */
    public function getChildren(): array|null
    {
        return $this->children;
    }

    /** @param Unit[] $ch */
    public function withChildren(array $ch): self
    {
        return new self(
            $this->id,
            $this->sortName,
            $this->displayName,
            $this->ic,
            $this->street,
            $this->city,
            $this->postcode,
            $this->registrationNumber,
            $this->type,
            $this->parentId,
            $ch,
        );
    }

    /** @return array<int|int> */
    public function getChildrenIds(): array
    {
        return array_map(function (Unit $unit): int {
            return $unit->getId();
        }, $this->children);
    }

    /** @return array<int|int> */
    public function getIdWithChildren(): array
    {
        return array_merge([$this->id], $this->getChildrenIds());
    }
}
