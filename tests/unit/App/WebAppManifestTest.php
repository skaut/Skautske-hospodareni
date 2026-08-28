<?php

declare(strict_types=1);

namespace App;

use Codeception\Test\Unit;

use function file_get_contents;
use function getimagesize;
use function json_decode;

use const IMAGETYPE_PNG;
use const JSON_THROW_ON_ERROR;

final class WebAppManifestTest extends Unit
{
    private const WWW_DIR = __DIR__.'/../../../www';

    public function testDescribesInstallableApplication(): void
    {
        $manifest = $this->manifest();

        self::assertSame('Skautské hospodaření', $manifest['name']);
        self::assertSame('Hospodaření', $manifest['short_name']);
        self::assertSame('/', $manifest['start_url']);
        self::assertSame('/', $manifest['scope']);
        self::assertSame('standalone', $manifest['display']);
        self::assertSame('#4d8746', $manifest['theme_color']);
        self::assertSame('#eef7fc', $manifest['background_color']);
    }

    /**
     * Android offers the installation only with a 192px and a 512px icon; without
     * a maskable variant the launcher puts the icon into a white box.
     */
    public function testShipsIconsRequiredForInstallation(): void
    {
        $sizesByPurpose = [];

        foreach ($this->manifest()['icons'] as $icon) {
            $file = self::WWW_DIR.$icon['src'];
            self::assertFileExists($file);

            $image = getimagesize($file);
            self::assertIsArray($image);
            self::assertSame(IMAGETYPE_PNG, $image[2]);
            self::assertSame('image/png', $icon['type']);
            self::assertSame($icon['sizes'], $image[0].'x'.$image[1]);

            $sizesByPurpose[$icon['purpose']][] = $icon['sizes'];
        }

        self::assertSame(['192x192', '512x512'], $sizesByPurpose['any']);
        self::assertSame(['192x192', '512x512'], $sizesByPurpose['maskable']);
    }

    /** iOS puts the home screen icon together from apple-touch-icon, not from the manifest. */
    public function testShipsAppleTouchIcon(): void
    {
        $image = getimagesize(self::WWW_DIR.'/images/pwa/icon-apple-touch.png');

        self::assertIsArray($image);
        self::assertSame(IMAGETYPE_PNG, $image[2]);
        self::assertSame([180, 180], [$image[0], $image[1]]);
    }

    /** The service worker caches the page during installation, so a missing file breaks it. */
    public function testShipsOfflineFallbackPage(): void
    {
        self::assertFileExists(self::WWW_DIR.'/offline.html');
    }

    /** @return array<string, mixed> */
    private function manifest(): array
    {
        return json_decode(
            (string) file_get_contents(self::WWW_DIR.'/manifest.webmanifest'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
