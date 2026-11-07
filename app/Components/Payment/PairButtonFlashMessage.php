<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Model\DTO\Payment\PairingResult;

use function array_map;
use function array_values;

final readonly class PairButtonFlashMessage
{
    public function __construct(public string $message, public string $type)
    {
    }

    /**
     * @param PairingResult[] $pairingResults
     *
     * @return list<self>
     */
    public static function fromPairingResults(array $pairingResults): array
    {
        if ($pairingResults === []) {
            return [new self('Nebyly nalezeny žádné úhrady k párování.', 'warning')];
        }

        return array_map(
            static fn (PairingResult $result): self => new self($result->getMessage(), $result->getCount() > 0 ? 'success' : 'info'),
            array_values($pairingResults),
        );
    }
}
