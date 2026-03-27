<?php

declare(strict_types=1);

namespace App\Components\Payment;

final readonly class PairButtonFlashMessage
{
    public function __construct(public string $message, public string $type)
    {
    }
}
