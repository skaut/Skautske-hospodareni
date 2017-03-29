<?php


namespace Model\Mail;

use Nette\Mail\IMailer;
use Nette\Mail\SmtpMailer;
use Mockery as m;

class MailerFactoryTest extends \Codeception\Test\Unit
{

    private const CREDENTIALS = [
        'host' => 'smtp.gmail.com',
        'username' => 'platby@skauting.cz',
        'password' => 'pass',
        'secure' => 'ssl',
    ];

    public function testInDisabledModeReturnsDebugMailer(): void
    {
        $mailer = m::mock(IMailer::class);
        $factory = new MailerFactory($mailer, FALSE);

        $this->assertSame($mailer, $factory->create(self::CREDENTIALS));
    }

    public function testInEnabledModeReturnsSmtpMailer(): void
    {
        $mailer = m::mock(IMailer::class);
        $factory = new MailerFactory($mailer, TRUE);

        $this->assertInstanceOf(SmtpMailer::class, $factory->create(self::CREDENTIALS));
    }

}
