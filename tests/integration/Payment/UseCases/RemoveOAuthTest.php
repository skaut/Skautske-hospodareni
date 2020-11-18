<?php

declare(strict_types=1);

namespace Model\Payment\IntegrationTests;

use DateTimeImmutable;
use Helpers;
use IntegrationTest;
use Model\Common\UnitId;
use Model\Google\Commands\RemoveOAuth;
use Model\Google\Exception\OAuthNotFound;
use Model\Google\Handlers\RemoveOAuthHandler;
use Model\Google\OAuth;
use Model\Mail\Repositories\IGoogleRepository;
use Model\Payment\Group;
use Model\Payment\Repositories\IGroupRepository;
use Stubs\BankAccountAccessCheckerStub;
use Stubs\OAuthsAccessCheckerStub;

final class RemoveOAuthTest extends IntegrationTest
{
    /** @var IGoogleRepository */
    private $repository;

    /** @var IGroupRepository */
    private $groups;

    /** @var RemoveOAuthHandler */
    private $handler;

    /**
     * @return string[]
     */
    protected function getTestedAggregateRoots() : array
    {
        return [
            OAuth::class,
            Group::class,
        ];
    }

    protected function _before() : void
    {
        $this->tester->useConfigFiles([__DIR__ . '/RemoveOAuthTest.neon']);
        parent::_before();

        $this->repository = $this->tester->grabService(IGoogleRepository::class);
        $this->groups     = $this->tester->grabService(IGroupRepository::class);
        $this->handler    = $this->tester->grabService(RemoveOAuthHandler::class);
    }

    public function test() : void
    {
        $oAuth = OAuth::create(new UnitId(123), 'code-xxx', 'test@hospodareni.loc');
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
