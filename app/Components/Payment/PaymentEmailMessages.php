<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Model\Payment\EmailType;
use App\Model\Payment\Mailing\PaymentEmailDispatch;

use function sprintf;

/** Flash messages about e-mails of payments, the same wherever they are sent from. */
final class PaymentEmailMessages
{
    private const LEFT_OUT_REASONS = [
        PaymentEmailDispatch::NO_RECIPIENT => 'platba nemá vyplněný e-mail',
        PaymentEmailDispatch::PAYMENT_CLOSED => 'platba je uzavřená',
        PaymentEmailDispatch::REMINDER_NOT_ALLOWED => 'platba není po splatnosti, je uzavřená, nebo už dnes upomínku dostala',
    ];

    /** @return list<array{string, string}> message and flash type */
    public static function forDispatch(PaymentEmailDispatch $dispatch, EmailType $type): array
    {
        if ($dispatch->blocked !== null) {
            return [[$dispatch->blocked, 'danger']];
        }

        $messages = [];

        if ($dispatch->queued > 0) {
            $messages[] = [self::queued($dispatch->queued), 'success'];
        }

        if ($dispatch->alreadyQueued > 0) {
            $messages[] = [sprintf('E-maily, které už čekaly ve frontě, se znovu nepřidaly (celkem %d).', $dispatch->alreadyQueued), 'info'];
        }

        $what = $type->equalsValue(EmailType::PAYMENT_REMINDER) ? 'Upomínku' : 'E-mail';

        foreach ($dispatch->leftOut as $reason => $count) {
            $messages[] = [
                sprintf('%s nejde poslat %s: %s.', $what, $count === 1 ? 'u 1 platby' : sprintf('u %d plateb', $count), self::LEFT_OUT_REASONS[$reason] ?? $reason),
                'warning',
            ];
        }

        if ($messages === []) {
            $messages[] = [$type->equalsValue(EmailType::PAYMENT_REMINDER) ? 'Žádná upomínka nebyla k odeslání.' : 'Žádný e-mail nebyl k odeslání.', 'info'];
        }

        return $messages;
    }

    /** Shown when the user stops something (closes the group, turns automatic reminders off) while e-mails still wait. */
    public static function stillWaiting(int $count): string
    {
        $what = match (true) {
            $count === 1 => 'čeká 1 e-mail',
            $count < 5 => sprintf('čekají %d e-maily', $count),
            default => sprintf('čeká %d e-mailů', $count),
        };

        return sprintf('Ve frontě ještě %s této skupiny. Pokud nemají odejít, zrušte je v panelu Odesílání e-mailů na pozadí.', $what);
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
