<?php

declare(strict_types=1);

namespace Tests\Unit\Bank\Services;

use App\Model\Bank\Services\CiFioTokenValidator;
use App\Model\Common\Embeddable\AccountNumber;
use Codeception\Test\Unit;
use InvalidArgumentException;

final class CiFioTokenValidatorTest extends Unit
{
    public function testConfiguredTokenForAccountPasses(): void
    {
        $validator = new CiFioTokenValidator([
            '1231231230/2010' => 'acceptance-fio-token',
        ]);

        $validator->validate(new AccountNumber(null, '1231231230', '2010'), 'acceptance-fio-token');

        self::assertTrue(true);
    }

    public function testInvalidTokenFailsWithUserMessage(): void
    {
        $validator = new CiFioTokenValidator([
            '1231231230/2010' => 'acceptance-fio-token',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fio token není platný nebo ještě není aktivní.');

        $validator->validate(new AccountNumber(null, '1231231230', '2010'), 'acceptance-invalid-fio-token');
    }
}
