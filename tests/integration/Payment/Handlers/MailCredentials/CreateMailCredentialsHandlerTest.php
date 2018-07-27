<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\MailCredentials;

use Model\Common\User;
use Model\Payment\Commands\CreateMailCredentials;
use Model\Payment\EmailNotSet;
use Model\Payment\MailCredentials;
use Model\Payment\MailCredentials\MailProtocol;
use Model\Payment\UserRepositoryStub;

class CreateMailCredentialsHandlerTest extends \CommandHandlerTest
{
    /** @var UserRepositoryStub */
    private $users;

    public function _before() : void
    {
        $this->tester->useConfigFiles(['Payment/Handlers/MailCredentials/CreateMailCredentialsHandlerTest.neon']);
        parent::_before();
        $this->users = $this->tester->grabService(UserRepositoryStub::class);
        $this->tester->resetEmails();
    }

    public function getTestedEntites() : array
    {
        return [
            MailCredentials::class,
        ];
    }

    public function testRecordToDatabaseIsAdded() : void
    {
        $this->users->setUser(new User(10, 'František Maša', 'test@hospodareni.loc'));

        $this->commandBus->handle($this->getCommand());

        $this->tester->seeInDatabase('pa_smtp', [
            'unitId' => 666,
            'host' => 'smtp-hospodareni.loc',
            'secure' => '',
            'username' => 'test@hospodareni.loc',
            'sender' => 'test@hospodareni.loc',
            'password' => '',
        ]);
    }

    public function testEmailIsSentToUser() : void
    {
        $this->users->setUser(new User(10, 'František Maša', 'test@hospodareni.loc'));

        $this->commandBus->handle($this->getCommand());

        $this->tester->seeEmailCount(1);
    }

    public function testExceptionIsThrownForUserWithoutEmail() : void
    {
        $this->users->setUser(new User(10, 'František Maša', null));

        $this->expectException(EmailNotSet::class);

        $this->commandBus->handle($this->getCommand());
    }

    private function getCommand() : CreateMailCredentials
    {
        return new CreateMailCredentials(
            666,
            'smtp-hospodareni.loc',
            'test@hospodareni.loc',
            '',
            MailProtocol::get(MailProtocol::PLAIN),
            'test@hospodareni.loc',
            10
        );
    }
}
