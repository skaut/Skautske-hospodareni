<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Model\Payment\EmailType;
use App\Model\Payment\Mailing\PaymentEmailDispatch;

use function sprintf;

/** Flash messages about e-mails of payments, the same wherever they are sent from. */
final class PaymentEmailMessages
{
    /** @return list<array{string, string}> message and flash type */
    public static function forDispatch(PaymentEmailDispatch $dispatch, EmailType $type): array
    {
        $messages = [];

        if ($dispatch->queued > 0) {
            $messages[] = [self::queued($dispatch->queued), 'success'];
        }

        if ($dispatch->alreadyQueued > 0) {
            $messages[] = [sprintf('E-maily, které už čekaly ve frontě, se znovu nepřidaly (celkem %d).', $dispatch->alreadyQueued), 'info'];
        }

        if ($dispatch->reminderNotAllowed > 0) {
            $messages[] = [self::reminderNotAllowed($dispatch->reminderNotAllowed), 'warning'];
        }

        if ($messages === []) {
            $messages[] = [$type->equalsValue(EmailType::PAYMENT_REMINDER) ? 'Žádná upomínka nebyla k odeslání.' : 'Žádný e-mail nebyl k odeslání.', 'info'];
        }

        return $messages;
    }

    private static function reminderNotAllowed(int $count): string
    {
        return sprintf(
            'Upomínku nejde poslat %s: platba není po splatnosti, je uzavřená, nebo už dnes upomínku dostala.',
            $count === 1 ? 'u 1 platby' : sprintf('u %d plateb', $count),
        );
    }

    private static function queued(int $count): string
    {
        $what = match (true) {
            $count === 1 => '1 e-mail byl zařazen k odeslání. Odchází',
            $count < 5 => sprintf('%d e-maily byly zařazeny k odeslání. Odcházejí', $count),
            default => sprintf('%d e-mailů bylo zařazeno k odeslání. Odcházejí', $count),
        };

        return $what.' na pozadí, průběh uvidíte na této stránce.';
    }
}
