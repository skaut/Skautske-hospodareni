<?php

declare(strict_types=1);

namespace Stubs;

use Model\Common\UnitId;
use Model\Google\OAuth;
use Model\Google\OAuthId;
use Model\Mail\Repositories\IGoogleRepository;

use function array_fill_keys;

class GoogleRepositoryStub implements IGoogleRepository
{
    public function save(OAuth $oAuth): void
    {
    }

    public function find(OAuthId $oauthId): OAuth
    {
        return $this->createOAuth();
    }

    /**
     * @param list<int> $unitIds
     *
     * @return array<int, list<OAuth>>
     */
    public function findByUnits(array $unitIds): array
    {
        return array_fill_keys($unitIds, []);
    }

    public function findByUnitAndEmail(UnitId $unitId, string $email): OAuth
    {
        return $this->createOAuth($unitId, $email);
    }

    public function remove(OAuth $oAuth): void
    {
    }

    private function createOAuth(UnitId|null $unitId = null, string|null $email = null): OAuth
    {
        return OAuth::create($unitId ?? new UnitId(123), 'XXXX', $email ?? 'test@hospodareni.loc');
    }
}
