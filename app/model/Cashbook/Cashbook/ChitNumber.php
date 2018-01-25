<?php

namespace Model\Cashbook\Cashbook;

use Nette\Utils\Strings;

final class ChitNumber
{

    public const PATTERN = '^[A-Z]{0,3}[0-9]{1,5}$';

    /** @var string */
    private $value;

    public function __construct(string $value)
    {
        if(strlen($value) > 5 || ! Strings::match($value, sprintf('~%s~', self::PATTERN))) {
            throw new \InvalidArgumentException('Chit number doesn\'t match required pattern');
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

}
