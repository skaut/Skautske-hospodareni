<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Model\Common\ValueObject;

final class CashbookId implements ValueObject
{

    /** @var string */
    private $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    public static function fromInt(int $id): self
    {
        return new self((string) $id);
    }

    /**
     * @deprecated This is only intermediate method, because CashbookId will wrap uuid soon
     */
    public function toInt(): int
    {
        return (int) $this->id;
    }

    public function toString(): string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function equals(ValueObject $otherValueObject): bool
    {
        return $otherValueObject instanceof self
            && $otherValueObject->id === $this->id;
    }

}
