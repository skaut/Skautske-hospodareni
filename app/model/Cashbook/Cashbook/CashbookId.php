<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;
use function is_numeric;
use function sprintf;

final class CashbookId
{
    /** @var string */
    private $id;

    private function __construct(string $id)
    {
        if (! is_numeric($id) && ! $this->isValidUuid($id)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid id "%s", valid ID is either UUIDv4 or legacy numeric string', $id)
            );
        }
        $this->id = $id;
    }

    public static function generate() : self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $id) : self
    {
        return new self($id);
    }

    public function toString() : string
    {
        return $this->id;
    }

    public function __toString() : string
    {
        return $this->toString();
    }

    public function equals(self $otherValueObject) : bool
    {
        return $otherValueObject->id === $this->id;
    }

    private function isValidUuid(string $id) : bool
    {
        try {
            return Uuid::fromString($id)->getVersion() === 4;
        } catch (InvalidUuidStringException $e) {
            return false;
        }
    }
}
