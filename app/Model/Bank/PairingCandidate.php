<?php

declare(strict_types=1);

namespace App\Model\Bank;

use App\Model\Invoice\Entity\Invoice;
use App\Model\Payment\Payment;
use InvalidArgumentException;

use function number_format;
use function spl_object_id;

final class PairingCandidate
{
    private function __construct(
        private readonly Payment|Invoice $target,
        private readonly int $variableSymbol,
        private readonly string $amount,
        private readonly string $type,
    ) {
    }

    public static function forPayment(Payment $payment): self
    {
        $variableSymbol = $payment->getVariableSymbol();

        if ($variableSymbol === null) {
            throw new InvalidArgumentException('Platba bez VS nemůže být kandidátem bankovního párování.');
        }

        return new self(
            $payment,
            $variableSymbol->toInt(),
            self::normalizeAmount($payment->getAmount()),
            'payment',
        );
    }

    public static function forInvoice(Invoice $invoice): self
    {
        return new self(
            $invoice,
            $invoice->getVariableSymbol()->toInt(),
            self::normalizeAmount((string) $invoice->getTotalAmount()),
            'invoice',
        );
    }

    public function getMatchKey(): string
    {
        return $this->variableSymbol.'|'.$this->amount;
    }

    public function getIdentityKey(): string
    {
        return $this->type.':'.spl_object_id($this->target);
    }

    public function getPayment(): ?Payment
    {
        return $this->target instanceof Payment ? $this->target : null;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->target instanceof Invoice ? $this->target : null;
    }

    private static function normalizeAmount(float|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
