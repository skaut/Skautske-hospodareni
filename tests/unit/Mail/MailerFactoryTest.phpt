<?php

namespace Tests\Unit\Mail;

use Dibi\Row;
use Model\Mail\MailerFactory;
use Model\Mail\MailerNotFoundException;
use Model\MailTable;
use Nette\Mail\IMailer;
use Nette\Mail\SmtpMailer;
use Tester\Assert;
use Tester\TestCase;

require __DIR__.'/../../bootstrap.php';

/**
 * @testCase
 */
class MailerFactoryTest extends TestCase
{

    public function tearDown() : void
    {
        \Mockery::close();
    }

    public function testNoSmtpRaisesException() : void
    {
        $smtpId = 22;
        $smtps = \Mockery::mock(MailTable::class);
        $smtps->shouldReceive('get')
            ->with($smtpId)
            ->andReturn(NULL);

        $factory = new MailerFactory(\Mockery::mock(IMailer::class), FALSE, $smtps);
        Assert::throws(function () use ($factory, $smtpId) {
            $factory->create($smtpId);
        }, MailerNotFoundException::class);
    }

    public function testNullSmtpIdRaisesException() : void
    {
        $factory = new MailerFactory(\Mockery::mock(IMailer::class), TRUE, \Mockery::mock(MailTable::class));
        Assert::throws(function() use($factory) {
            $factory->create(NULL);
        }, MailerNotFoundException::class);
    }

    public function testExistingSmtpInDisabledModeReturnsDebugMailer() : void
    {
        $smtpId = 22;
        $smtps = \Mockery::mock(MailTable::class);
        $smtps->shouldReceive('get')
            ->with($smtpId)
            ->andReturn($this->getRow());

        $mailer = \Mockery::mock(IMailer::class);
        $factory = new MailerFactory($mailer, FALSE, $smtps);

        Assert::same($mailer, $factory->create($smtpId));
    }

    public function testExistingSmtpInEnabledModeReturnsSmtpMailer() : void
    {
        $smtpId = 22;
        $smtps = \Mockery::mock(MailTable::class);
        $smtps->shouldReceive('get')
            ->with($smtpId)
            ->andReturn($this->getRow());

        $mailer = \Mockery::mock(IMailer::class);
        $factory = new MailerFactory($mailer, TRUE, $smtps);

        Assert::type(SmtpMailer::class, $factory->create($smtpId));
    }

    private function getRow() : Row
    {
        return new Row([
            'host' => 'smtp.gmail.com',
            'username' => 'platby@skauting.cz',
            'password' => 'pass',
            'secure' => 'ssl',
        ]);
    }

}
(new MailerFactoryTest())->run();
