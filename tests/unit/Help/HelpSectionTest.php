<?php

declare(strict_types=1);

namespace App\Model\Help;

use Codeception\Test\Unit;
use InvalidArgumentException;

final class HelpSectionTest extends Unit
{
    public function testTrimsHeadingAndText(): void
    {
        $section = HelpSection::create('  Platnost tři roky  ', "  Dopočítá se sama.\n");

        $this->assertSame('Platnost tři roky', $section->getHeading());
        $this->assertSame('Dopočítá se sama.', $section->getText());
        $this->assertSame([], $section->getItems());
        $this->assertFalse($section->hasItems());
    }

    public function testDropsEmptyItems(): void
    {
        $section = HelpSection::create('Nadpis', 'Text', ['  %vs%  ', '', '   ', '%ks%']);

        $this->assertSame(['%vs%', '%ks%'], $section->getItems());
        $this->assertTrue($section->hasItems());
    }

    public function testRejectsEmptyHeading(): void
    {
        $this->expectException(InvalidArgumentException::class);

        HelpSection::create('   ', 'Text');
    }

    public function testRejectsEmptyText(): void
    {
        $this->expectException(InvalidArgumentException::class);

        HelpSection::create('Nadpis', '');
    }

    public function testSurvivesRoundTripThroughArray(): void
    {
        $section = HelpSection::create('Nadpis', 'Text', ['odrážka']);
        $restored = HelpSection::fromArray($section->toArray());

        $this->assertSame($section->getHeading(), $restored->getHeading());
        $this->assertSame($section->getText(), $restored->getText());
        $this->assertSame($section->getItems(), $restored->getItems());
    }

    public function testAcceptsArrayWithoutItemsKey(): void
    {
        $restored = HelpSection::fromArray(['heading' => 'Nadpis', 'text' => 'Text']);

        $this->assertSame([], $restored->getItems());
    }
}
