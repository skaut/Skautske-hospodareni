<?php

declare(strict_types=1);

namespace App;

use Codeception\Test\Unit;

use function hash;
use function strtoupper;

final class CronTokenTest extends Unit
{
    private const TOKEN = '6f1c0e0f4c3b4f0c9b5a6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a';

    public function testAcceptsBearerToken(): void
    {
        $this->assertTrue(CronToken::isAuthorized($this->hash(), ['HTTP_AUTHORIZATION' => 'Bearer '.self::TOKEN], []));
    }

    public function testAcceptsBearerTokenForwardedByRewrite(): void
    {
        $this->assertTrue(CronToken::isAuthorized($this->hash(), ['REDIRECT_HTTP_AUTHORIZATION' => 'Bearer '.self::TOKEN], []));
    }

    public function testAcceptsTokenInPostBody(): void
    {
        $this->assertTrue(CronToken::isAuthorized(strtoupper($this->hash()), [], ['token' => self::TOKEN]));
    }

    public function testRejectsWrongToken(): void
    {
        $this->assertFalse(CronToken::isAuthorized($this->hash(), [], ['token' => self::TOKEN.'x']));
    }

    public function testRejectsTokenThatIsTheConfiguredHash(): void
    {
        $this->assertFalse(CronToken::isAuthorized($this->hash(), [], ['token' => $this->hash()]));
    }

    public function testRejectsShortToken(): void
    {
        $token = 'short';

        $this->assertFalse(CronToken::isAuthorized(hash('sha256', $token), [], ['token' => $token]));
    }

    public function testRejectsEveryCallWhenHashIsNotConfigured(): void
    {
        $this->assertFalse(CronToken::isAuthorized('', [], ['token' => self::TOKEN]));
        $this->assertFalse(CronToken::isAuthorized(self::TOKEN.'0', [], ['token' => self::TOKEN]));
    }

    public function testRejectsMissingToken(): void
    {
        $this->assertFalse(CronToken::isAuthorized($this->hash(), ['HTTP_AUTHORIZATION' => 'Basic '.self::TOKEN], ['token' => ['x']]));
    }

    private function hash(): string
    {
        return hash('sha256', self::TOKEN);
    }
}
