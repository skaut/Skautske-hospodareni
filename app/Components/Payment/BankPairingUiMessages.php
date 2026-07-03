<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Model\Bank\Exception\BankWrongTokenAccount;

final class BankPairingUiMessages
{
    public const TIMEOUT_MESSAGE = 'Nepodařilo se připojit k bankovnímu serveru. Zkontrolujte svůj API token pro přístup k účtu.';
    public const TIME_LIMIT_MESSAGE = 'Mezi dotazy na bankovnictví musí být prodleva 1 minuta!';

    public static function wrongTokenAccountMessage(BankWrongTokenAccount $exception): string
    {
        return 'Zadaný API token patří ke špatnému bankovnímu účtu. Zadaný bankovní účet je '.$exception->getIntendedAccount().', token patří k účtu '.$exception->getTokenAccount().'.';
    }
}
