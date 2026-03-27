<?php

declare(strict_types=1);

namespace App\Model\Commands\Payment;

use App\Model\Handlers\Payment\UpdateNoteHandler;

/** @see UpdateNoteHandler */
final class UpdateNote
{
    public function __construct(
        private int $paymentId,
        private string $note,
    ) {
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }

    public function getNote(): string
    {
        return $this->note;
    }
}
