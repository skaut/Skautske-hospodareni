<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use App\Model\Common\Embeddable\AccountNumber;
use InvalidArgumentException;

use function sprintf;

final class CiFioTokenValidator implements FioTokenValidatorInterface
{
    /** @param array<string, string> $tokensByAccount */
    public function __construct(private readonly array $tokensByAccount)
    {
    }

    public function validate(AccountNumber $accountNumber, string $token): void
    {
        $account = sprintf('%s/%s', $accountNumber->getNumber(), $accountNumber->getBankCode());

        if (($this->tokensByAccount[$account] ?? null) === $token) {
            return;
        }

        throw new InvalidArgumentException('Fio token není platný nebo ještě není aktivní. Nově vytvořený token lze podle Fio použít až po 5 minutách od autorizace.');
    }
}
