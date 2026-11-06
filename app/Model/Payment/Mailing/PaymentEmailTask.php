<?php

declare(strict_types=1);

namespace App\Model\Payment\Mailing;

use App\Model\Payment\EmailType;
use InvalidArgumentException;

use function is_numeric;
use function is_string;

/** Payload of one queued e-mail of a payment. */
final class PaymentEmailTask
{
    public function __construct(
        public readonly int $paymentId,
        public readonly EmailType $emailType,
        public readonly bool $automatic = false,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromPayload(array $payload): self
    {
        $paymentId = $payload['paymentId'] ?? null;
        $emailType = $payload['emailType'] ?? null;

        if (! is_numeric($paymentId) || ! is_string($emailType)) {
            throw new InvalidArgumentException('Invalid payload of a payment e-mail task.');
        }

        return new self((int) $paymentId, EmailType::get($emailType), (bool) ($payload['automatic'] ?? false));
    }

    /** @return array{paymentId: int, emailType: string, automatic: bool} */
    public function toPayload(): array
    {
        return [
            'paymentId' => $this->paymentId,
            'emailType' => $this->emailType->getValue(),
            'automatic' => $this->automatic,
        ];
    }

    /**
     * The same e-mail of the same payment never waits in the queue twice. An automatic reminder and
     * a reminder sent by hand are the same e-mail, so only one of them can wait or run at a time.
     */
    public function dedupKey(): string
    {
        return PaymentEmailTaskHandler::TYPE.':'.$this->emailType->getValue().':'.$this->paymentId;
    }
}
