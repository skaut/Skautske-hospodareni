<?php

declare(strict_types=1);

namespace Model\User;

use Nette\Utils\Strings;

class SkautisRole
{
    private const LEADER_PREFIX        = 'vedouci';
    private const ACCOUNTANT_PREFIX    = 'hospodar';
    private const OFFICER_PREFIX       = 'cinovnik';
    private const EVENT_MANAGER_PREFIX = 'spravceAkci';

    private const BASIC_UNIT_SUFFIX = 'Stredisko';
    private const TROOP_SUFFIX      = 'Oddil';

    private string $key;

    private string $name;

    private int $unitId;

    private string $unitName;

    public function __construct(
        string $key,
        string $name,
        int $unitId,
        string $unitName
    ) {
        $this->key      = $key;
        $this->name     = $name;
        $this->unitId   = $unitId;
        $this->unitName = $unitName;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getUnitName() : string
    {
        return $this->unitName;
    }

    public function isLeader() : bool
    {
        return Strings::startsWith($this->key, self::LEADER_PREFIX);
    }

    public function isAccountant() : bool
    {
        return Strings::startsWith($this->key, self::ACCOUNTANT_PREFIX);
    }

    public function isOfficer() : bool
    {
        return Strings::startsWith($this->key, self::OFFICER_PREFIX);
    }

    public function isEventManager() : bool
    {
        return Strings::startsWith($this->key, self::EVENT_MANAGER_PREFIX);
    }

    public function isBasicUnit() : bool
    {
        return Strings::endsWith($this->key, self::BASIC_UNIT_SUFFIX);
    }

    public function isTroop() : bool
    {
        return Strings::endsWith($this->key, self::TROOP_SUFFIX);
    }
}
