<?php

declare(strict_types=1);

namespace Model\Mail;

use Codeception\Test\Unit;
use Google_Client;
use Mockery as m;
use Model\Google\GoogleService;
use Model\Google\OAuth;
use Model\Google\OAuthMailer;
use Nette\Mail\IMailer;

class MailerFactoryTest extends Unit
{
    public function testInDisabledModeReturnsDebugMailer() : void
    {
        $googleService = m::mock(GoogleService::class);
        $mailer        = m::mock(IMailer::class);
        $factory       = new MailerFactory($mailer, false, $googleService);

        $this->assertSame($mailer, $factory->create($this->getConfig()));
    }

    public function testInEnabledModeReturnsSmtpMailer() : void
    {
        $googleService = m::mock(GoogleService::class, [
            'getClient' => m::mock(Google_Client::class, [
                'fetchAccessTokenWithRefreshToken' => ['token' => 'MyToken'],
                'setAccessToken' => null,
            ]),
        ]);
        $mailer        = m::mock(IMailer::class);
        $factory       = new MailerFactory($mailer, true, $googleService);

        $this->assertInstanceOf(OAuthMailer::class, $factory->create($this->getConfig()));
    }

    private function getConfig() : OAuth
    {
        $mock = m::mock(OAuth::class);
        $mock->shouldReceive(['getToken' => 'XXX']);

        return $mock;
    }
}
