<?php


namespace Model\Mail;

use Model\Payment\MailCredentials;
use Nette\Mail\IMailer;
use Nette\Mail\SmtpMailer;
use Mockery as m;

class MailerFactoryTest extends \Codeception\Test\Unit
{

    public function testInDisabledModeReturnsDebugMailer(): void
    {
        $mailer = m::mock(IMailer::class);
        $factory = new MailerFactory($mailer, FALSE);

        $this->assertSame($mailer, $factory->create($this->getConfig()));
    }

    public function testInEnabledModeReturnsSmtpMailer(): void
    {
        $mailer = m::mock(IMailer::class);
        $factory = new MailerFactory($mailer, TRUE);

        $this->assertInstanceOf(SmtpMailer::class, $factory->create($this->getConfig()));
    }

    private function getConfig(): MailCredentials
    {
        $mock = m::mock(MailCredentials::class);
        $mock->shouldReceive([
            'getHost' => 'smtp.gmail.com',
            'getUsername' => 'platby@skauting.cz',
            'getPassword' => 'pass',
            'getProtocol' => MailCredentials\MailProtocol::get(MailCredentials\MailProtocol::SSL),
        ]);

        return $mock;
    }

}
