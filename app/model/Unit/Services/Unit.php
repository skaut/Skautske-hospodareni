<?php

declare(strict_types=1);

namespace Model\Unit;

use Nette\Utils\Strings;
use function end;
use function explode;
use function in_array;

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

    /** @var string */
    private $registrationNumber;

    /** @var string */
    private $type;

    /** @var int|NULL */
    private $parentId;

    /** @var Unit[]|null */
    private $children;

    public function __construct(
        int $id,
        string $sortName,
        string $displayName,
        string $registrationNumber,
        string $type,
        ?int $parentId,
        ?array $children = null
    ) {
        $this->id                 = $id;
        $this->sortName           = $sortName;
        $this->displayName        = $displayName;
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
        return new self($this->id, $this->sortName, $this->displayName, $this->registrationNumber, $this->type, $this->parentId, $ch);
    }
}
