<?php

declare(strict_types=1);

namespace App\Model\Payment\Repositories;

use App\Model\Google\OAuthId;
use App\Model\Payment\Group;
use App\Model\Payment\Group\SkautisEntity;
use App\Model\Payment\Group\Type;
use App\Model\Payment\GroupNotFound;

interface IGroupRepository
{
    /** @throws GroupNotFound */
    public function find(int $id): Group;

    /**
     * @param int[] $ids
     *
     * @return Group[]
     */
    public function findByIds(array $ids): array;

    /** @return Group[] */
    public function findByReminder(): array;

    /**
     * @param int[] $unitIds
     *
     * @return Group[]
     */
    public function findByUnits(array $unitIds, bool $openOnly): array;

    /** @return Group[] */
    public function findBySkautisEntities(SkautisEntity ...$objects): array;

    /** @return Group[] */
    public function findBySkautisEntityType(Type $type): array;

    /** @return Group[] */
    public function findByBankAccount(int $bankAccountId): array;

    /** @return Group[] */
    public function findByOAuth(OAuthId $oAuthId): array;

    public function save(Group $group): void;

    public function remove(Group $group): void;
}
