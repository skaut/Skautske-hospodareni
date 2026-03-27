<?php

declare(strict_types=1);

namespace App\Model\Payment;

use RuntimeException;

final class BankTransactionSourceChangeNotAllowed extends RuntimeException
{
}
