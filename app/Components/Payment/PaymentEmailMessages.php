<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Model\Payment\EmailType;
use App\Model\Payment\Mailing\PaymentEmailDispatch;
use App\Model\TaskQueue\TaskItemState;

use function sprintf;

/** Flash messages about e-mails of payments, the same wherever they are sent from. */
final class PaymentEmailMessages
{
    /** @return list<array{string, string}> message and flash type */
    public static function forDispatch(PaymentEmailDispatch $dispatch, EmailType $type): array
    {
        if ($dispatch->sentNow !== null) {
            return [self::forSentNow($dispatch->sentNow->state, $dispatch->sentNow->message, $type)];
        }

        $messages = [];

        if ($dispatch->queued > 0) {
            $messages[] = [self::queued($dispatch->queued), 'success'];
        }

        if ($dispatch->alreadyQueued > 0) {
            $messages[] = [sprintf('E-maily, které už čekaly ve frontě, se znovu nepřidaly (celkem %d).', $dispatch->alreadyQueued), 'info'];
        }

        if ($messages === []) {
            $messages[] = ['Žádný e-mail nebyl k odeslání.', 'info'];
        }

        return $messages;
    }

    /** @return array{string, string} */
    private static function forSentNow(TaskItemState $state, ?string $reason, EmailType $type): array
    {
        return match ($state) {
            TaskItemState::DONE => [$type->equalsValue(EmailType::PAYMENT_REMINDER) ? 'Upomínka byla odeslána.' : 'E-mail byl odeslán.', 'success'],
            TaskItemState::PENDING => [sprintf('E-mail se odešle později: %s', $reason), 'info'],
            TaskItemState::UNCERTAIN => ['Nepodařilo se ověřit, jestli e-mail odešel. Než ho pošlete znovu, zkontrolujte odeslanou poštu e-mailu odesílatele skupiny.', 'warning'],
            TaskItemState::SKIPPED => [sprintf('E-mail se neodeslal: %s', $reason), 'warning'],
            default => [sprintf('E-mail se nepodařilo odeslat: %s', $reason), 'danger'],
        };
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
