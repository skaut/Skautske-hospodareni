<?php

declare(strict_types=1);

namespace App\Model\Payment;

use RuntimeException;

use function sprintf;

final class VariableSymbolCollision extends RuntimeException
{
    public static function forBankAccount(VariableSymbol $variableSymbol): self
    {
        return new self(sprintf(
            'Variabilní symbol %s je už použitý u jiné otevřené bankovní platby nebo faktury na stejném účtu.',
            (string) $variableSymbol,
        ));
    }
}
