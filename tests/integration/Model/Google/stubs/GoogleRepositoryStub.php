<?php

declare(strict_types=1);

namespace Stubs;

use Entity\GoogleOAuth;
use Model\Common\UnitId;
use Model\Google\OAuthId;
use Model\Mail\Repositories\IGoogleRepository;

use function array_fill_keys;

class GoogleRepositoryStub implements IGoogleRepository
{
    public function save(GoogleOAuth $oAuth): void
    {
    }

    public function find(OAuthId $oauthId): GoogleOAuth
    {
        return $this->createOAuth();
    }

    /**
     * @param list<int> $unitIds
     *
     * @return array<int, list<GoogleOAuth>>
     */
    public function findByUnits(array $unitIds): array
    {
        return array_fill_keys($unitIds, []);
    }

    public function findByUnitAndEmail(UnitId $unitId, string $email): GoogleOAuth
    {
        return $this->createOAuth($unitId, $email);
    }

    public function remove(GoogleOAuth $oAuth): void
    {
    }

    private function createOAuth(?UnitId $unitId = null, ?string $email = null): GoogleOAuth
    {
        return GoogleOAuth::create($unitId ?? new UnitId(123), 'XXXX', $email ?? 'test@hospodareni.loc');
    }
}
