<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\MailCredentials;

use Model\Payment\Commands\RemoveMailCredentials;
use Model\Payment\MailCredentials;

class RemoveMailCredentialsHandlerTest extends \CommandHandlerTest
{
    public function _before() : void
    {
        $this->tester->useConfigFiles(['Payment/Handlers/MailCredentials/RemoveMailCredentialsHandlerTest.neon']);
        parent::_before();
    }

    /**
     * @return string[]
     */
    public function getTestedEntites() : array
    {
        return [
            MailCredentials::class,
        ];
    }

    public function testRemoveExistingCredentials() : void
    {
        $this->tester->haveInDatabase('pa_smtp', [
            'unitId' => 666,
            'host' => 'smtp-hospodareni.loc',
            'secure' => '',
            'username' => 'test@hospodareni.loc',
            'password' => '',
            'sender' => 'test@hospodareni.loc',
            'created' => '2017-10-01 00:00:00',
        ]);

        $this->commandBus->handle(new RemoveMailCredentials(1));

        $this->tester->dontSeeInDatabase('pa_smtp', ['id' => 1]);
    }

    public function testRemoveNonexistentCredentialsSilentlyProceeds() : void
    {
        $this->commandBus->handle(new RemoveMailCredentials(20));
    }
}
