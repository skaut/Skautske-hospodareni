<?php

declare(strict_types=1);

namespace App\Model\User;

use Nette\Utils\Strings;

class SkautisRole
{
    private const LEADER_PREFIX = 'vedouci';
    private const ACCOUNTANT_PREFIX = 'hospodar';
    private const OFFICER_PREFIX = 'cinovnik';
    private const EVENT_MANAGER_PREFIX = 'spravceAkci';

    private const BASIC_UNIT_SUFFIX = 'Stredisko';
    private const TROOP_SUFFIX = 'Oddil';

    public function __construct(
        private string $key,
        private string $name,
        private int $unitId,
        private string $unitName,
    ) {
    }

    /** @return array{key: string, name: string, unitId: int, unitName: string} */
    public function __serialize(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'unitId' => $this->unitId,
            'unitName' => $this->unitName,
        ];
    }

    /** @param array<string, mixed> $data */
    public function __unserialize(array $data): void
    {
        // New format (clean keys from __serialize)
        if (isset($data['key'])) {
            $this->key = $data['key'];
            $this->name = $data['name'];
            $this->unitId = $data['unitId'];
            $this->unitName = $data['unitName'];

            return;
        }

        // Legacy format: private props serialized as "\0ClassName\0prop"
        // Extract values regardless of the class name prefix
        $values = [];
        foreach ($data as $k => $v) {
            // Strip \0ClassName\0 prefix to get the bare property name
            $bare = preg_replace('/^\x00.*\x00/', '', $k);
            $values[$bare] = $v;
        }

        $this->key = (string) ($values['key'] ?? '');
        $this->name = (string) ($values['name'] ?? '');
        $this->unitId = (int) ($values['unitId'] ?? 0);
        $this->unitName = (string) ($values['unitName'] ?? '');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getUnitName(): string
    {
        return $this->unitName;
    }

    public function isLeader(): bool
    {
        return Strings::startsWith($this->key, self::LEADER_PREFIX);
    }

    public function isAccountant(): bool
    {
        return Strings::startsWith($this->key, self::ACCOUNTANT_PREFIX) || $this->key === 'EventEducationEconomist';
    }

    public function isEducationLeader(): bool
    {
        return $this->key === 'EventEducationLeader';
    }

    public function isOfficer(): bool
    {
        return Strings::startsWith($this->key, self::OFFICER_PREFIX);
    }

    public function isEventManager(): bool
    {
        return Strings::startsWith($this->key, self::EVENT_MANAGER_PREFIX);
    }

    public function isBasicUnit(): bool
    {
        return Strings::endsWith($this->key, self::BASIC_UNIT_SUFFIX);
    }

    public function isTroop(): bool
    {
        return Strings::endsWith($this->key, self::TROOP_SUFFIX);
    }
}

// Backward compatibility for session-serialized objects created before namespace migration
class_alias(SkautisRole::class, 'Model\User\SkautisRole');
