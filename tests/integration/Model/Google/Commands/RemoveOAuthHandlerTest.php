<?php

declare(strict_types=1);

namespace App\Model\Google\Handlers;

use App\Model\Google\Commands\RemoveOAuth;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Google\OAuthId;
use App\Model\Payment\Group;
use CommandHandlerTest;

class RemoveOAuthHandlerTest extends CommandHandlerTest
{
    public function _before(): void
    {
        $this->tester->useConfigFiles(['Model/Google/Commands/RemoveOAuthHandlerTest.neon']);

        parent::_before();
    }

    /** @return string[] */
    public function getTestedAggregateRoots(): array
    {
        return [
            GoogleOAuth::class,
            Group::class,
        ];
    }

    public function testRemoveExistingCredentials(): void
    {
        $id = '42288e92-27fb-453c-9904-36a7ebd14fe2';
        $this->tester->haveInDatabase('google_oauth', [
            'id' => $id,
            'unit_id' => 27266,
            'email' => 'test@hospodareni.loc',
            'token' => '1//02yV7BM31saaQCgYIAPOOREPSNwF-L9Irbcw-iJEHRUnfxt2KULTjXQkPI-jl8LEN-SwVp6OybduZT21RiDf7RZBA4ZoZu86UXC8',
            'updated_at' => '2017-06-15 00:00:00',
        ]);

        $this->commandBus->handle(new RemoveOAuth(OAuthId::fromString($id)));

        $this->tester->dontSeeInDatabase('google_oauth', ['id' => $id]);
    }

    public function testRemoveNonexistentOAuthSilentlyProceeds(): void
    {
        $this->commandBus->handle(new RemoveOAuth(OAuthId::generate()));
    }
}
