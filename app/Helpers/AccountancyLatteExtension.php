<?php

declare(strict_types=1);

namespace App\Helpers;

use Latte\Extension;

/**
 * Registruje účetní Latte filtry (Latte 3 způsob – nahrazuje deprecated addFilterLoader).
 */
final class AccountancyLatteExtension extends Extension
{
    /** @return array<string, callable> */
    public function getFilters(): array
    {
        return [
            'eventStateLabel' => AccountancyHelpers::eventStateLabel(...),
            'educationStateLabel' => AccountancyHelpers::educationStateLabel(...),
            'grantStateLabel' => AccountancyHelpers::grantStateLabel(...),
            'campStateLabel' => AccountancyHelpers::campStateLabel(...),
            'commandState' => AccountancyHelpers::commandState(...),
            'paymentState' => AccountancyHelpers::paymentState(...),
            'paymentStateLabel' => AccountancyHelpers::paymentStateLabel(...),
            'price' => AccountancyHelpers::price(...),
            'num' => AccountancyHelpers::num(...),
            'priceToString' => AccountancyHelpers::priceToString(...),
            'groupState' => AccountancyHelpers::groupState(...),
            'dateRange' => AccountancyHelpers::dateRange(...),
        ];
    }
}
