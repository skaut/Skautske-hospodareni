<?php

namespace Model\Unit;

use Model\UnitService;
use Nette\Utils\Strings;

class Unit
{

    private const OFFICIAL_UNIT_TYPES = [
        'stredisko',
        'kraj',
        'okres',
        'ustredi',
        'zvlastniJednotka'
    ];
    
    /** @var int */
    private $id;

    /** @var string */
    private $sortName;

    /** @var string */
    private $displayName;

    /** @var string */
    private $registrationNumber;

    /** @var string */
    private $type;

    /** @var int|NULL */
    private $parentId;


    public function __construct(
        int $id,
        string $sortName,
        string $displayName,
        string $registrationNumber,
        string $type,
        ?int $parentId
    )
    {
        $this->id = $id;
        $this->sortName = $sortName;
        $this->displayName = $displayName;
        $this->registrationNumber = $registrationNumber;
        $this->type = $type;
        $this->parentId = $parentId;
    }


    public function isSubunitOf(Unit $unit): bool
    {
        return Strings::startsWith($this->registrationNumber, $unit->registrationNumber);
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

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function isOfficial(): bool
    {
        return in_array($this->type, self::OFFICIAL_UNIT_TYPES, TRUE);
    }

}
