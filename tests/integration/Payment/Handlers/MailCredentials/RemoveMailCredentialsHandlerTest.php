<?php

namespace Model\Payment\Handlers\MailCredentials;

use Model\Payment\Commands\RemoveMailCredentials;
use Model\Payment\MailCredentials;

class RemoveMailCredentialsHandlerTest extends \CommandHandlerTest
{

    public function _before()
    {
        $this->tester->useConfigFiles([
            'Payment/Handlers/MailCredentials/RemoveMailCredentialsHandlerTest.neon',
        ]);
        parent::_before();
    }

    public function getTestedEntites(): array
    {
        return [
            MailCredentials::class,
        ];
    }

    public function testRemoveExistingCredentials()
    {
        $this->tester->haveInDatabase('pa_smtp', [
            'unitId' => 666,
            'host' => 'smtp-hospodareni.loc',
            'secure' => '',
            'username' => 'test@hospodareni.loc',
            'password' => '',
            'created' => '2017-10-01 00:00:00',
        ]);

        $this->commandBus->handle(new RemoveMailCredentials(1));

        $this->tester->dontSeeInDatabase('pa_smtp', ['id' => 1]);
    }

    public function testRemoveNonexistentCredentialsSilentlyProceeds()
    {
        $this->commandBus->handle(new RemoveMailCredentials(20));
    }

}
