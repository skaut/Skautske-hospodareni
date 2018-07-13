<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Codeception\Test\Unit;

class RecipientTest extends Unit
{
    public function testToString() : void
    {
        $name      = 'František Maša';
        $recipient = new Recipient($name);

        $this->assertSame($name, (string) $recipient);
    }

    public function testCantCreateRecipientWithoutName() : void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Recipient('');
    }
}
