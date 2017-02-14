<?php

namespace Tests\Unit\Mail;

use Mockery\MockInterface;
use Model\Mail\MailerFactory;
use Model\MailService;
use Model\MailTable;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__.'/../../bootstrap.php';

/**
 * @testCase
 */
class MailServiceTest extends TestCase
{

    public function testDefaultMailer()
    {
        $groupId = 10;

        $table = \Mockery::mock(MailTable::class);
        $table->shouldReceive('getSmtpByGroup')
            ->with($groupId)
            ->andReturn([]);

        $message = new Message();

        $defaultMailer = $this->mockMailer($message);
        $mailerFactory = \Mockery::mock(MailerFactory::class);

        $service = new MailService($table, $mailerFactory, $defaultMailer);

        $service->send($message, $groupId);
    }

    public function testSmtpMailer()
    {
        $groupId = 10;

        $smtp = [
            'host' => 'smtp.gmail.com',
            'username' => 'test@gmail.com',
            'password' => 'pass123',
            'secure' => 'ssl',
        ];

        $table = \Mockery::mock(MailTable::class);
        $table->shouldReceive('getSmtpByGroup')
            ->with($groupId)
            ->andReturn($smtp);

        $message = new Message();

        $defaultMailer = \Mockery::mock(IMailer::class);

        $mailerFactory = \Mockery::mock(MailerFactory::class);
        $mailerFactory->shouldReceive('create')
            ->withArgs(array_values($smtp))
            ->andReturn($this->mockMailer($message));

        $service = new MailService($table, $mailerFactory, $defaultMailer);

        $service->send($message, $groupId);
    }

    /**
     * @param Message $expectedMail
     * @return IMailer|MockInterface
     */
    private function mockMailer(Message $expectedMail)
    {
        $mailer = \Mockery::mock(IMailer::class);
        $mailer->shouldReceive('send')
            ->once()
            ->withArgs(function(Message $msg) use ($expectedMail) {
                Assert::same($expectedMail, $msg);
                Assert::same([MailService::EMAIL_SENDER => NULL], $msg->getFrom());
                return TRUE;
            });
        return $mailer;
    }

    public function tearDown()
    {
        \Mockery::close();
    }

}
(new MailServiceTest())->run();
