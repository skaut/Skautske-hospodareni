<?php

declare(strict_types=1);

namespace App\Model\Help;

use App\Model\Help\Entity\PageHelp;
use Codeception\Test\Unit;
use DateTimeImmutable;
use InvalidArgumentException;

final class PageHelpTest extends Unit
{
    public function testCanonicalizesYouTubeShareUrlAndRemovesTrackingParameters(): void
    {
        $help = new PageHelp(
            'Settings:User:default',
            null,
            [],
            '  Nastavení uživatele  ',
            'https://youtu.be/kfVsfOSbJY0?si=source&utm_source=newsletter',
            new DateTimeImmutable(),
            null,
        );

        self::assertSame('Nastavení uživatele', $help->getYoutubeTitle());
        self::assertSame('https://www.youtube.com/watch?v=kfVsfOSbJY0', $help->getYoutubeUrl());
        self::assertTrue($help->hasContent());
    }

    public function testRejectsNonYoutubeAndIncompleteVideoDetails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Zadejte odkaz na konkrétní video YouTube.');

        new PageHelp(
            'Settings:User:default',
            null,
            [],
            'Název videa',
            'https://example.com/video',
            new DateTimeImmutable(),
            null,
        );
    }
}
