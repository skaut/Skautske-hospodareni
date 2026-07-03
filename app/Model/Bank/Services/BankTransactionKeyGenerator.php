<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use DateTimeImmutable;

use function hash;
use function implode;
use function number_format;
use function trim;

final class BankTransactionKeyGenerator
{
    public function fromFio(string $transactionId): string
    {
        return trim($transactionId);
    }

    public function fromGpc(
        string $accountNumber,
        DateTimeImmutable $date,
        float $amount,
        ?string $counterAccount,
        string $name,
        ?int $variableSymbol,
        ?int $constantSymbol,
        ?string $note,
    ): string {
        $canonical = implode('|', [
            'gpc',
            $accountNumber,
            $date->format('Y-m-d'),
            number_format($amount, 2, '.', ''),
            trim((string) $counterAccount),
            trim($name),
            (string) $variableSymbol,
            (string) $constantSymbol,
            trim((string) $note),
        ]);

        return 'gpc:'.hash('sha256', $canonical);
    }
}
