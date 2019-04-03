<?php

declare(strict_types=1);

namespace Model\Unit;

use Nette\Utils\Strings;
use function end;
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

    /** @var int */
    private $id;

    /** @var string */
    private $sortName;

    /** @var string */
    private $displayName;

    /** @var string|null */
    private $ic;

    /** @var string */
    private $street;

    /** @var string */
    private $city;

    /** @var string */
    private $postcode;

    /** @var string */
    private $registrationNumber;

    /** @var string */
    private $type;

    /** @var int|NULL */
    private $parentId;

    /** @var Unit[]|null */
    private $children;

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


    public function isSubunitOf(Unit $unit) : bool
    {
        return Strings::startsWith($this->registrationNumber, $unit->registrationNumber);
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
        return $this->getFullDisplayName() . ', ' . $this->street . ', ' . $this->city . ', ' . $this->postcode . ', IČO: ' . $this->ic;
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

        return end($splitNumber);
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
