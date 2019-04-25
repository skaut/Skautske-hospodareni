<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use InvalidArgumentException;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;
use function is_numeric;
use function sprintf;
use function str_replace;

final class CashbookId
{
    /** @var string */
    private $id;

    private function __construct(string $id)
    {
        $normalizedId = $this->normalize($id);

        if ($normalizedId === null) {
            throw new InvalidArgumentException(
                sprintf('Invalid id "%s", valid ID is either UUIDv4 or legacy numeric string', $id)
            );
        }
        $this->id = $normalizedId;
    }

    public static function generate() : self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $id) : self
    {
        return new self($id);
    }

    public function withoutHyphens() : string
    {
        if (is_numeric($this->id)) {
            return $this->id;
        }

        return str_replace('-', '', $this->id);
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

    private function normalize(string $id) : ?string
    {
        try {
            $uuid = Uuid::fromString($id);

            if ($uuid->getVersion() === 4) {
                return $uuid->toString(); // valid UUID
            }
        } catch (InvalidUuidStringException $e) {
            if (is_numeric($id)) {
                return $id; // legacy ID
            }
        }

        return null;
    }
}
