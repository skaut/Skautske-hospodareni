<?php

declare(strict_types=1);

namespace App\Model\User\Services;

use App\Model\User\DeviceInfo;

use function explode;
use function mb_substr;
use function preg_match;
use function trim;

/**
 * Turns a User-Agent into a coarse device / browser / platform triple.
 *
 * Hand-rolled on purpose — a full detection library carries thousands of rules
 * for devices this application will never see, and the three buckets we report
 * on need nothing more than the patterns below.
 *
 * Client hints refine the result where the browser sends them. Only Chromium
 * does, so they upgrade the guess and never replace it.
 */
final class DeviceClassifier
{
    private const MAX_VERSION_LENGTH = 16;
    private const MAX_PLATFORM_LENGTH = 32;

    /** Ordered — Edge, Opera and Samsung all carry "Chrome" in their User-Agent. */
    private const BROWSER_PATTERNS = [
        'Edge' => '~edg(?:e|a|ios)?/([0-9.]+)~i',
        'Opera' => '~(?:opr|opera)[/ ]([0-9.]+)~i',
        'Samsung Internet' => '~samsungbrowser/([0-9.]+)~i',
        'Firefox' => '~(?:firefox|fxios)/([0-9.]+)~i',
        'Chrome' => '~(?:chrome|crios)/([0-9.]+)~i',
        'Safari' => '~version/([0-9.]+).*safari~i',
    ];

    private const PLATFORM_PATTERNS = [
        'iOS' => '~iphone|ipad|ipod~i',
        'Android' => '~android~i',
        'Windows' => '~windows nt~i',
        'macOS' => '~mac os x~i',
        'Linux' => '~linux~i',
    ];

    private const BOT_PATTERN = '~bot\b|crawl|spider|slurp|headless|curl/|wget/|monitoring~i';

    /** Android tablets omit "Mobile" from the User-Agent; phones keep it. */
    private const TABLET_PATTERN = '~ipad|tablet|kindle|playbook|silk|android(?!.*mobile)~i';

    private const MOBILE_PATTERN = '~mobile|iphone|ipod|android|blackberry|opera mini|iemobile|windows phone~i';

    /**
     * @param string|null $secChUaMobile   value of the Sec-CH-UA-Mobile header ("?1" / "?0")
     * @param string|null $secChUaPlatform value of the Sec-CH-UA-Platform header (a quoted string)
     */
    public function classify(?string $userAgent, ?string $secChUaMobile = null, ?string $secChUaPlatform = null): DeviceInfo
    {
        $userAgent = $userAgent !== null ? trim($userAgent) : '';

        if ($userAgent === '') {
            return DeviceInfo::unknown();
        }

        [$browser, $version] = $this->detectBrowser($userAgent);

        return new DeviceInfo(
            $this->detectType($userAgent, $secChUaMobile),
            $browser,
            $version,
            $this->detectPlatform($userAgent, $secChUaPlatform),
        );
    }

    private function detectType(string $userAgent, ?string $secChUaMobile): string
    {
        if (preg_match(self::BOT_PATTERN, $userAgent) === 1) {
            return DeviceInfo::TYPE_BOT;
        }

        if (preg_match(self::TABLET_PATTERN, $userAgent) === 1) {
            return DeviceInfo::TYPE_TABLET;
        }

        if (preg_match(self::MOBILE_PATTERN, $userAgent) === 1) {
            return DeviceInfo::TYPE_MOBILE;
        }

        // Chromium says so outright. Trust it only to upgrade a desktop guess —
        // it reports "?0" for Android tablets, which would undo the check above.
        if ($secChUaMobile !== null && trim($secChUaMobile) === '?1') {
            return DeviceInfo::TYPE_MOBILE;
        }

        return DeviceInfo::TYPE_DESKTOP;
    }

    /** @return array{0: string, 1: string|null} */
    private function detectBrowser(string $userAgent): array
    {
        foreach (self::BROWSER_PATTERNS as $name => $pattern) {
            if (preg_match($pattern, $userAgent, $matches) !== 1) {
                continue;
            }

            return [$name, $this->majorVersion($matches[1])];
        }

        // Safari without a Version/ token — older iOS webviews land here.
        if (preg_match('~safari/([0-9.]+)~i', $userAgent) === 1) {
            return ['Safari', null];
        }

        return [DeviceInfo::UNKNOWN_LABEL, null];
    }

    private function detectPlatform(string $userAgent, ?string $secChUaPlatform): string
    {
        $hint = $secChUaPlatform !== null ? trim($secChUaPlatform, " \t\n\r\0\x0B\"") : '';

        if ($hint !== '') {
            return mb_substr($hint, 0, self::MAX_PLATFORM_LENGTH);
        }

        foreach (self::PLATFORM_PATTERNS as $name => $pattern) {
            if (preg_match($pattern, $userAgent) !== 1) {
                continue;
            }

            return $name;
        }

        return DeviceInfo::UNKNOWN_LABEL;
    }

    /** Major version only — a build number would be a fingerprint, not a metric. */
    private function majorVersion(string $version): ?string
    {
        $major = explode('.', $version)[0];

        return $major !== '' ? mb_substr($major, 0, self::MAX_VERSION_LENGTH) : null;
    }
}
