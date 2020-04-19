<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Codeception\Test\Unit;

final class CashbookIdTest extends Unit
{
    private const UUID                 = '340139ce-8059-429c-ad1a-349909679619';
    private const UUID_WITHOUT_HYPHENS = '340139ce8059429cad1a349909679619';

    public function testFromStringWithUuid() : void
    {
        $this->assertSame(self::UUID, CashbookId::fromString(self::UUID)->toString());
    }

    public function testFromStringWithUuidWithoutHyphens() : void
    {
        $this->assertSame(self::UUID, CashbookId::fromString(self::UUID_WITHOUT_HYPHENS)->toString());
    }

    public function testWithoutHyphensWithUuidRemovesHyphens() : void
    {
        $this->assertSame(
            self::UUID_WITHOUT_HYPHENS,
            CashbookId::fromString(self::UUID_WITHOUT_HYPHENS)->withoutHyphens()
        );
    }
}
