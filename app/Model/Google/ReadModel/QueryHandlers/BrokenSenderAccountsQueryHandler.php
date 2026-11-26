<?php

declare(strict_types=1);

namespace App\Model\Google\ReadModel\QueryHandlers;

use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Google\BrokenSenderAccount;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Google\Exception\OAuthNotFound;
use App\Model\Google\ReadModel\Queries\BrokenSenderAccountsQuery;
use App\Model\Mail\Repositories\IGoogleRepository;
use App\Model\Skautis\Exception\MissingCurrentRole;
use App\Model\Unit\ReadModel\Queries\UnitQuery;
use App\Model\Unit\UnitNotFound;
use App\Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use App\Model\User\ReadModel\Queries\EditableUnitsQuery;
use App\Model\User\SkautisRole;
use DateTimeImmutable;

use function array_keys;
use function array_map;
use function in_array;
use function sprintf;

/**
 * The senders Google refuses and whether the current user can fix them. An account is connected again
 * under the active role (see the Google settings presenter), so only a role of the account's own unit
 * that may edit it can do so.
 */
final class BrokenSenderAccountsQueryHandler
{
    public function __construct(private IGoogleRepository $repository, private QueryBus $queryBus)
    {
    }

    /** @return list<BrokenSenderAccount> in the order of the given senders, each once */
    public function __invoke(BrokenSenderAccountsQuery $query): array
    {
        /** @var array<string, GoogleOAuth> $broken by id, so a sender of more groups counts once */
        $broken = [];

        foreach ($query->getOAuthIds() as $oAuthId) {
            try {
                $oAuth = $this->repository->find($oAuthId);
            } catch (OAuthNotFound) {
                continue;
            }

            if (! $oAuth->isInvalid()) {
                continue;
            }

            $broken[$oAuth->getId()->toString()] = $oAuth;
        }

        if ($broken === []) {
            return [];
        }

        $role = $this->findActiveRole();
        $editableUnits = $role === null
            ? []
            : array_map('intval', array_keys($this->queryBus->handle(new EditableUnitsQuery($role))));

        $accounts = [];

        foreach ($broken as $oAuth) {
            $unitId = $oAuth->getUnitId()->toInt();

            $accounts[] = new BrokenSenderAccount(
                $oAuth->getId()->toString(),
                $oAuth->getEmail(),
                $unitId,
                $this->unitName($unitId),
                $oAuth->getInvalidSince() ?? new DateTimeImmutable(),
                $role !== null && $role->getUnitId() === $unitId && in_array($unitId, $editableUnits, true),
            );
        }

        return $accounts;
    }

    private function findActiveRole(): ?SkautisRole
    {
        try {
            return $this->queryBus->handle(new ActiveSkautisRoleQuery());
        } catch (MissingCurrentRole) {
            return null;
        }
    }

    private function unitName(int $unitId): string
    {
        try {
            return $this->queryBus->handle(new UnitQuery($unitId))->getDisplayName();
        } catch (UnitNotFound) {
            return sprintf('č. %d', $unitId);
        }
    }
}
