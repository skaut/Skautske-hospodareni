<?php

declare(strict_types=1);

namespace App\Model\Payment\IntegrationTests;

use App\Model\Common\UnitId;
use App\Model\Google\Commands\RemoveOAuth;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Google\Exception\OAuthNotFound;
use App\Model\Google\Handlers\RemoveOAuthHandler;
use App\Model\Mail\Repositories\IGoogleRepository;
use App\Model\Payment\Group;
use App\Model\Payment\Repositories\IGroupRepository;
use DateTimeImmutable;
use Helpers;
use IntegrationTest;
use Stubs\BankAccountAccessCheckerStub;
use Stubs\OAuthsAccessCheckerStub;

final class RemoveOAuthTest extends IntegrationTest
{
    private IGoogleRepository $repository;

    private IGroupRepository $groups;

    private RemoveOAuthHandler $handler;

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [
            GoogleOAuth::class,
            Group::class,
        ];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__.'/RemoveOAuthTest.neon']);

        parent::_before();

        $this->repository = $this->tester->grabService(IGoogleRepository::class);
        $this->groups = $this->tester->grabService(IGroupRepository::class);
        $this->handler = $this->tester->grabService(RemoveOAuthHandler::class);
    }

    public function test(): void
    {
        $oAuth = GoogleOAuth::create(new UnitId(123), 'code-xxx', 'test@hospodareni.loc');
        $this->repository->save($oAuth);
        $oAuthId = $oAuth->getId();

        $group = new Group(
            [123],
            null,
            'test',
            Helpers::createEmptyPaymentDefaults(),
            new DateTimeImmutable(),
            Helpers::createEmails(),
            $oAuthId,
            null,
            new BankAccountAccessCheckerStub(),
            new OAuthsAccessCheckerStub(),
        );
        $this->groups->save($group);
        $this->assertSame(1, $group->getId());

        ($this->handler)(new RemoveOAuth($oAuthId));

        $this->assertNull($this->groups->find($group->getId())->getOauthId());

        $this->expectException(OAuthNotFound::class);
        $this->repository->find($oAuthId);
    }
}
