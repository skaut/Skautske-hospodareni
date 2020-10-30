<?php

declare(strict_types=1);

namespace Model\Mail;

use Codeception\Test\Unit;
use Mockery as m;
use Model\Google\OAuth;
use Nette\Mail\IMailer;
use Nette\Mail\SmtpMailer;

class MailerFactoryTest extends Unit
{
    public function testInDisabledModeReturnsDebugMailer() : void
    {
        $mailer  = m::mock(IMailer::class);
        $factory = new MailerFactory($mailer, false);

        $this->assertSame($mailer, $factory->create($this->getConfig()));
    }

    public function testInEnabledModeReturnsSmtpMailer() : void
    {
        $mailer  = m::mock(IMailer::class);
        $factory = new MailerFactory($mailer, true);

        $this->assertInstanceOf(SmtpMailer::class, $factory->create($this->getConfig()));
    }

    private function getConfig() : OAuth
    {
        $mock = m::mock(OAuth::class);
        $mock->shouldReceive(['getToken' => 'XXX']);

        return $mock;
    }
}
