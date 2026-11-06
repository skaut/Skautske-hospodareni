<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Model\Payment\EmailType;
use App\Model\Payment\Mailing\PaymentEmailDispatch;
use App\Model\TaskQueue\EnqueueResult;
use Codeception\Test\Unit;

final class PaymentEmailMessagesTest extends Unit
{
    /** @dataProvider queuedCounts */
    public function testQueuedEmailsAreCountedInCzech(int $count, string $expected): void
    {
        $messages = PaymentEmailMessages::forDispatch(
            PaymentEmailDispatch::fromResult(new EnqueueResult(1, $count, 0)),
            EmailType::get(EmailType::PAYMENT_INFO),
        );

        self::assertSame([[$expected, 'success']], $messages);
    }

    /** @return array<string, array{int, string}> */
    public static function queuedCounts(): array
    {
        return [
            'one' => [1, '1 e-mail byl zařazen k odeslání. Odchází na pozadí, průběh uvidíte na této stránce.'],
            'few' => [3, '3 e-maily byly zařazeny k odeslání. Odcházejí na pozadí, průběh uvidíte na této stránce.'],
            'many' => [12, '12 e-mailů bylo zařazeno k odeslání. Odcházejí na pozadí, průběh uvidíte na této stránce.'],
        ];
    }

    public function testEmailsAlreadyInQueueAreReported(): void
    {
        $messages = PaymentEmailMessages::forDispatch(
            PaymentEmailDispatch::fromResult(new EnqueueResult(null, 0, 2)),
            EmailType::get(EmailType::PAYMENT_INFO),
        );

        self::assertSame([['E-maily, které už čekaly ve frontě, se znovu nepřidaly (celkem 2).', 'info']], $messages);
    }

    public function testRemindersThatCannotGoOutAreReported(): void
    {
        $reminder = EmailType::get(EmailType::PAYMENT_REMINDER);

        self::assertSame(
            [
                ['1 e-mail byl zařazen k odeslání. Odchází na pozadí, průběh uvidíte na této stránce.', 'success'],
                ['Upomínku nejde poslat u 25 plateb: platba není po splatnosti, je uzavřená, nebo už dnes upomínku dostala.', 'warning'],
            ],
            PaymentEmailMessages::forDispatch(new PaymentEmailDispatch(1, 0, 25), $reminder),
        );
        self::assertSame(
            [['Upomínku nejde poslat u 1 platby: platba není po splatnosti, je uzavřená, nebo už dnes upomínku dostala.', 'warning']],
            PaymentEmailMessages::forDispatch(new PaymentEmailDispatch(0, 0, 1), $reminder),
        );
    }
}
