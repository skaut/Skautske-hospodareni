<?php

declare(strict_types=1);

namespace Model\Payment\IntegrationTests;

use DateTimeImmutable;
use Helpers;
use IntegrationTest;
use Model\Payment\Commands\RemoveMailCredentials;
use Model\Payment\Group;
use Model\Payment\Handlers\MailCredentials\RemoveMailCredentialsHandler;
use Model\Payment\MailCredentials;
use Model\Payment\MailCredentialsNotFound;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Repositories\IMailCredentialsRepository;
use Stubs\BankAccountAccessCheckerStub;

final class RemoveMailCredentialsTest extends IntegrationTest
{
    /** @var IMailCredentialsRepository */
    private $credentials;

    /** @var IGroupRepository */
    private $groups;

    /** @var RemoveMailCredentialsHandler */
    private $handler;

    /**
     * @return string[]
     */
    protected function getTestedEntites() : array
    {
        return [
            MailCredentials::class,
            Group::class,
            Group\Email::class,
            Group\Unit::class,
        ];
    }

    protected function _before() : void
    {
        $this->tester->useConfigFiles([__DIR__ . '/RemoveMailCredentialsTest.neon']);
        parent::_before();

        $this->credentials = $this->tester->grabService(IMailCredentialsRepository::class);
        $this->groups      = $this->tester->grabService(IGroupRepository::class);
        $this->handler     = $this->tester->grabService(RemoveMailCredentialsHandler::class);
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
        $this->credentials->save($credentials);
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
            new BankAccountAccessCheckerStub()
        );
        $this->groups->save($group);
        $this->assertSame(1, $group->getId());

        ($this->handler)(new RemoveMailCredentials($credentialsId));

        $this->assertNull($this->groups->find($group->getId())->getSmtpId());

        $this->expectException(MailCredentialsNotFound::class);
        $this->credentials->find($credentialsId);
    }
}
