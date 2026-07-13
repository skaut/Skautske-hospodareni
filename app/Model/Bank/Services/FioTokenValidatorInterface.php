<?php

declare(strict_types=1);

namespace App\Model\Bank\Services;

use App\Model\Common\Embeddable\AccountNumber;

interface FioTokenValidatorInterface
{
    public function validate(AccountNumber $accountNumber, string $token): void;
}
