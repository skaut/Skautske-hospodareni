<?php

namespace Tests\Unit\Mail;

use Model\Mail\MailerFactory;
use Nette\Mail\IMailer;
use Nette\Mail\SmtpMailer;
use Tester\Assert;
use Tester\TestCase;

require __DIR__.'/../../bootstrap.php';

class MailerFactoryTest extends TestCase
{

    private const CONFIG = [
        'host' => 'smtp.gmail.com',
        'username' => 'platby@skauting.cz',
        'password' => 'pass',
        'secure' => 'ssl',
    ];

    public function tearDown()
    {
        \Mockery::close();
    }

    private function create(MailerFactory $factory)
    {
        $config = self::CONFIG;
        return $factory->create($config['host'], $config['username'], $config['password'], $config['secure']);
    }

    public function testDisabled()
    {
        $mailer = \Mockery::mock(IMailer::class);
        $factory = new MailerFactory($mailer, FALSE);
        Assert::same($mailer, $this->create($factory));
    }

    public function testEnabled()
    {
        $factory = new MailerFactory(\Mockery::mock(IMailer::class), TRUE);
        $mailer = $this->create($factory);
        Assert::type(SmtpMailer::class, $mailer);
    }

}
(new MailerFactoryTest())->run();
