<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Nette\Utils\Strings;
use function ctype_digit;
use function sprintf;
use function strlen;
use function strtoupper;

final class ChitNumber
{
    public const PATTERN = '^[a-zA-Z]{0,3}[0-9]{1,5}(/[0-9]{1,2})?$';

    /** @var string */
    private $value;

    public function __construct(string $value)
    {
        $value = strtoupper($value);
        if (strlen($value) > 5 || ! Strings::match($value, sprintf('~%s~', self::PATTERN))) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not valid chit number', $value)
            );
        }

        $this->value = $value;
    }

    public function isContainsChar() : bool
    {
        return $this->value !== '' && ! ctype_digit($this->value);
    }

    public function toString() : string
    {
        return $this->value;
    }

    public function __toString() : string
    {
        return $this->toString();
    }
}
