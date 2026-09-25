<?php

declare(strict_types=1);

namespace App\Model\User\Services;

use App\Model\User\DeviceInfo;
use Codeception\Test\Unit as TestCase;

final class DeviceClassifierTest extends TestCase
{
    private DeviceClassifier $classifier;

    protected function _before(): void
    {
        $this->classifier = new DeviceClassifier();
    }

    /** @dataProvider provideUserAgents */
    public function testUserAgentIsClassified(
        string $userAgent,
        string $expectedType,
        string $expectedBrowser,
        ?string $expectedVersion,
        string $expectedPlatform,
    ): void {
        $device = $this->classifier->classify($userAgent);

        self::assertSame($expectedType, $device->getType());
        self::assertSame($expectedBrowser, $device->getBrowser());
        self::assertSame($expectedVersion, $device->getBrowserVersion());
        self::assertSame($expectedPlatform, $device->getPlatform());
    }

    /** @return array<string, array{string, string, string, string|null, string}> */
    public static function provideUserAgents(): array
    {
        return [
            'Chrome na Windows' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
                DeviceInfo::TYPE_DESKTOP,
                'Chrome',
                '130',
                'Windows',
            ],
            'Firefox na Linuxu' => [
                'Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0',
                DeviceInfo::TYPE_DESKTOP,
                'Firefox',
                '131',
                'Linux',
            ],
            'Safari na macOS' => [
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15',
                DeviceInfo::TYPE_DESKTOP,
                'Safari',
                '17',
                'macOS',
            ],
            'Safari na iPhonu' => [
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Mobile/15E148 Safari/604.1',
                DeviceInfo::TYPE_MOBILE,
                'Safari',
                '17',
                'iOS',
            ],
            'Chrome na Androidu' => [
                'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36',
                DeviceInfo::TYPE_MOBILE,
                'Chrome',
                '130',
                'Android',
            ],
            'iPad je tablet' => [
                'Mozilla/5.0 (iPad; CPU OS 17_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/604.1',
                DeviceInfo::TYPE_TABLET,
                'Safari',
                '17',
                'iOS',
            ],
            'Android bez Mobile je tablet' => [
                'Mozilla/5.0 (Linux; Android 13; SM-X200) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
                DeviceInfo::TYPE_TABLET,
                'Chrome',
                '129',
                'Android',
            ],
            'Edge se nehlásí jako Chrome' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.2849.68',
                DeviceInfo::TYPE_DESKTOP,
                'Edge',
                '130',
                'Windows',
            ],
            'Opera se nehlásí jako Chrome' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36 OPR/115.0.0.0',
                DeviceInfo::TYPE_DESKTOP,
                'Opera',
                '115',
                'Windows',
            ],
            'Samsung Internet se nehlásí jako Chrome' => [
                'Mozilla/5.0 (Linux; Android 13; SM-S911B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/23.0 Chrome/115.0.0.0 Mobile Safari/537.36',
                DeviceInfo::TYPE_MOBILE,
                'Samsung Internet',
                '23',
                'Android',
            ],
            'Googlebot je robot' => [
                'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                DeviceInfo::TYPE_BOT,
                DeviceInfo::UNKNOWN_LABEL,
                null,
                DeviceInfo::UNKNOWN_LABEL,
            ],
        ];
    }

    public function testEmptyUserAgentIsUnknown(): void
    {
        $device = $this->classifier->classify(null);

        self::assertSame(DeviceInfo::TYPE_UNKNOWN, $device->getType());
        self::assertSame(DeviceInfo::UNKNOWN_LABEL, $device->getBrowser());
        self::assertNull($device->getBrowserVersion());
        self::assertSame(DeviceInfo::UNKNOWN_LABEL, $device->getPlatform());
    }

    public function testClientHintUpgradesDesktopGuessToMobile(): void
    {
        // A Chromium UA with no mobile token — only the hint gives it away.
        $userAgent = 'Mozilla/5.0 (Unknown) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';

        self::assertSame(DeviceInfo::TYPE_DESKTOP, $this->classifier->classify($userAgent)->getType());
        self::assertSame(DeviceInfo::TYPE_MOBILE, $this->classifier->classify($userAgent, '?1')->getType());
    }

    public function testClientHintNeverDowngradesTabletToDesktop(): void
    {
        // Chromium reports "?0" on Android tablets; the UA already said tablet.
        $userAgent = 'Mozilla/5.0 (Linux; Android 13; SM-X200) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36';

        self::assertSame(DeviceInfo::TYPE_TABLET, $this->classifier->classify($userAgent, '?0')->getType());
    }

    public function testPlatformHintWinsOverUserAgent(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';

        self::assertSame('Chrome OS', $this->classifier->classify($userAgent, null, '"Chrome OS"')->getPlatform());
    }
}
