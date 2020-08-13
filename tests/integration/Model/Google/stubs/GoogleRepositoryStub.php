<?php

declare(strict_types=1);

namespace Stubs;

use Google_Service_Gmail;
use Mockery as m;
use Model\Common\UnitId;
use Model\Google\OAuth;
use Model\Google\OAuthId;
use Model\Mail\Repositories\IGoogleRepository;
use function array_fill_keys;

class GoogleRepositoryStub implements IGoogleRepository
{
    public function getAuthUrl() : string
    {
        return '';
    }

    public function saveAuthCode(string $code, UnitId $unitId) : void
    {
    }

    public function find(OAuthId $oauthId) : ?OAuth
    {
        return OAuth::create(new UnitId(123), 'XXXX', 'test@hospodareni.loc');
    }

    /**
     * @inheritDoc
     */
    public function findByUnits(array $unitIds) : array
    {
        return array_fill_keys($unitIds, []);
    }

    /**
     * @inheritDoc
     */
    public function findByUnit(UnitId $unitId) : array
    {
        return [];
    }

    public function remove(OAuth $oAuth) : void
    {
    }

    public function getGmailService(OAuth $oAuth) : Google_Service_Gmail
    {
         return m::mock(Google_Service_Gmail::class);
    }
}
