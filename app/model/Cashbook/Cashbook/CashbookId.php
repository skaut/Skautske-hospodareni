<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

final class CashbookId
{

    /** @var string */
    private $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @deprecated Use self::fromString
     */
    public static function fromInt(int $id): self
    {
        return new self((string) $id);
    }

    public static function fromString(string $id): self
    {
        return new self($id);
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

    public function equals(self $otherValueObject): bool
    {
        return $otherValueObject->id === $this->id;
    }

}
