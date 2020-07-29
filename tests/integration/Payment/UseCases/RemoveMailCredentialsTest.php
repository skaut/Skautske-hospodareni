<?php

declare(strict_types=1);

namespace Model\Payment\IntegrationTests;

use DateTimeImmutable;
use Helpers;
use IntegrationTest;
use Model\Google\OAuthNotFound;
use Model\Mail\Repositories\IGoogleRepository;
use Model\Payment\Commands\RemoveMailCredentials;
use Model\Payment\Group;
use Model\Payment\MailCredentials;
use Model\Payment\Repositories\IGroupRepository;
use Stubs\BankAccountAccessCheckerStub;
use Stubs\OAuthsAccessCheckerStub;

final class RemoveMailCredentialsTest extends IntegrationTest
{
    /** @var IGoogleRepository */
    private $googleRepository;

    /** @var IGroupRepository */
    private $groups;

    /** @var RemoveMailCredentialsHandler */
    private $handler;

    /**
     * @return string[]
     */
    protected function getTestedAggregateRoots() : array
    {
        return [
            MailCredentials::class,
            Group::class,
        ];
    }

    protected function _before() : void
    {
        $this->tester->useConfigFiles([__DIR__ . '/RemoveMailCredentialsTest.neon']);
        parent::_before();

        $this->googleRepository = $this->tester->grabService(IGoogleRepository::class);
        $this->groups           = $this->tester->grabService(IGroupRepository::class);
        $this->handler          = $this->tester->grabService(RemoveMailCredentialsHandler::class);
    }

    public function test() : void
    {
        $credentials = new MailCredentials(
            54,
            'mail.google.xxx',
            'jan',
            'pass',
            MailCredentials\MailProtocol::SSL(),
            'me@mail.cz',
            new DateTimeImmutable()
        );
        $this->googleRepository->save($credentials);
        $this->assertSame(1, $credentials->getId());
        $credentialsId = $credentials->getId();

        $group = new Group(
            [123],
            null,
            'test',
            Helpers::createEmptyPaymentDefaults(),
            new DateTimeImmutable(),
            Helpers::createEmails(),
            $credentialsId,
            null,
            new BankAccountAccessCheckerStub(),
            new OAuthsAccessCheckerStub(),
        );
        $this->groups->save($group);
        $this->assertSame(1, $group->getId());

        ($this->handler)(new RemoveMailCredentials($credentialsId));

        $this->assertNull($this->groups->find($group->getId())->getOauthId());

        $this->expectException(OAuthNotFound::class);
        $this->googleRepository->find($credentialsId);
    }
}
