<?php


namespace Model\Mail;

use Dibi\Row;
use Model\MailTable;
use Nette\Mail\IMailer;
use Nette\Mail\SmtpMailer;
use Mockery as m;

class MailerFactoryTest extends \Codeception\Test\Unit
{

    public function testNoSmtpRaisesException(): void
    {
        $smtpId = 22;
        $smtps = m::mock(MailTable::class);
        $smtps->shouldReceive('get')
            ->with($smtpId)
            ->andReturn(NULL);

        $factory = new MailerFactory(m::mock(IMailer::class), FALSE, $smtps);

        $this->expectException(MailerNotFoundException::class);
        $factory->create($smtpId);
    }

    public function testNullSmtpIdRaisesException(): void
    {
        $factory = new MailerFactory(m::mock(IMailer::class), TRUE, m::mock(MailTable::class));
        $this->expectException(MailerNotFoundException::class);
        $factory->create(NULL);
    }

    public function testExistingSmtpInDisabledModeReturnsDebugMailer(): void
    {
        $smtpId = 22;
        $smtps = m::mock(MailTable::class);
        $smtps->shouldReceive('get')
            ->with($smtpId)
            ->andReturn($this->getRow());

        $mailer = m::mock(IMailer::class);
        $factory = new MailerFactory($mailer, FALSE, $smtps);

        $this->assertSame($mailer, $factory->create($smtpId));
    }

    public function testExistingSmtpInEnabledModeReturnsSmtpMailer(): void
    {
        $smtpId = 22;
        $smtps = m::mock(MailTable::class);
        $smtps->shouldReceive('get')
            ->with($smtpId)
            ->andReturn($this->getRow());

        $mailer = m::mock(IMailer::class);
        $factory = new MailerFactory($mailer, TRUE, $smtps);

        $this->assertInstanceOf(SmtpMailer::class, $factory->create($smtpId));
    }

    private function getRow(): Row
    {
        return new Row([
            'host' => 'smtp.gmail.com',
            'username' => 'platby@skauting.cz',
            'password' => 'pass',
            'secure' => 'ssl',
        ]);
    }

    protected function _after()
    {
        m::close();
    }

}
