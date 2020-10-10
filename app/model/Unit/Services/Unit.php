<?php

declare(strict_types=1);

namespace Model\Unit;

use function array_key_last;
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

    private int $id;

    private string $sortName;

    private string $displayName;

    private ?string $ic;

    private string $street;

    private string $city;

    private string $postcode;

    private string $registrationNumber;

    private string $type;

    private ?int $parentId;

    /** @var Unit[]|null */
    private ?array $children;

    /**
     * @param Unit[]|null $children
     */
    public function __construct(
        int $id,
        string $sortName,
        string $displayName,
        ?string $ic,
        string $street,
        string $city,
        string $postcode,
        string $registrationNumber,
        string $type,
        ?int $parentId,
        ?array $children = null
    ) {
        $this->id                 = $id;
        $this->sortName           = $sortName;
        $this->displayName        = $displayName;
        $this->ic                 = $ic;
        $this->street             = $street;
        $this->city               = $city;
        $this->postcode           = $postcode;
        $this->registrationNumber = $registrationNumber;
        $this->type               = $type;
        $this->parentId           = $parentId;
        $this->children           = $children;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getSortName() : string
    {
        return $this->sortName;
    }

    public function getDisplayName() : string
    {
        return $this->displayName;
    }

    public function getFullDisplayName() : string
    {
        if ($this->isOfficial()) {
            return sprintf('Junák - český skaut, %s, z. s.', $this->getDisplayName());
        }

        return '';
    }

    public function getFullDisplayNameWithAddress() : string
    {
        return $this->getFullDisplayName() . ', ' . $this->getAddress() . ', IČO: ' . $this->ic;
    }

    public function getAddress() : string
    {
        return $this->street . ', ' . $this->city . ', ' . $this->postcode;
    }

    public function getIc() : ?string
    {
        return $this->ic;
    }

    public function getStreet() : string
    {
        return $this->street;
    }

    public function getCity() : string
    {
        return $this->city;
    }

    public function getPostcode() : ?string
    {
        return $this->postcode;
    }

    public function getParentId() : ?int
    {
        return $this->parentId;
    }

    public function isOfficial() : bool
    {
        return in_array($this->type, self::OFFICIAL_UNIT_TYPES, true);
    }

    public function getShortRegistrationNumber() : string
    {
        $splitNumber = explode('.', $this->registrationNumber);

        return $splitNumber[array_key_last($splitNumber)];
    }

    public function getRegistrationNumber() : string
    {
        return $this->registrationNumber;
    }

    /**
     * @return Unit[]|null
     */
    public function getChildren() : ?array
    {
        return $this->children;
    }

    /**
     * @param Unit[] $ch
     */
    public function withChildren(array $ch) : self
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
            $ch
        );
    }
}
