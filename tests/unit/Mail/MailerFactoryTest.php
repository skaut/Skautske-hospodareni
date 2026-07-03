<?php

declare(strict_types=1);

namespace App\Model\Mail;

use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Google\GoogleService;
use App\Model\Google\OAuthMailer;
use Codeception\Test\Unit;
use Google_Client;
use Mockery as m;
use Nette\Mail\Mailer;

class MailerFactoryTest extends Unit
{
    public function testInDisabledModeReturnsDebugMailer(): void
    {
        $googleService = m::mock(GoogleService::class);
        $mailer = m::mock(Mailer::class);
        $factory = new MailerFactory($mailer, false, $googleService);

        $this->assertSame($mailer, $factory->create($this->getConfig()));
    }

    public function testInEnabledModeReturnsSmtpMailer(): void
    {
        $googleService = m::mock(GoogleService::class, [
            'getClient' => m::mock(Google_Client::class, [
                'fetchAccessTokenWithRefreshToken' => ['token' => 'MyToken'],
                'setAccessToken' => null,
            ]),
        ]);
        $mailer = m::mock(Mailer::class);
        $factory = new MailerFactory($mailer, true, $googleService);

        $this->assertInstanceOf(OAuthMailer::class, $factory->create($this->getConfig()));
    }

    private function getConfig(): GoogleOAuth
    {
        $mock = m::mock(GoogleOAuth::class);
        $mock->shouldReceive(['getToken' => 'XXX']);

        return $mock;
    }
}
