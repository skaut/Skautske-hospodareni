<?php

declare(strict_types=1);

namespace Model\Cashbook;

class CashbookNotFoundException extends \Exception
{
}

class ChitNotFoundException extends \Exception
{
}

class ChitLockedException extends \Exception
{
}

class InvalidCashbookTransferException extends \Exception
{
}

class CategoryNotFoundException extends \Exception
{
}
