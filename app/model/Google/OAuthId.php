<?php

declare(strict_types=1);

namespace Model\Google;

use InvalidArgumentException;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;
use function sprintf;

final class OAuthId
{
    /** @var string */
    private $id;

    /**
     * @throws InvalidUuidStringException
     * @throws InvalidArgumentException
     */
    private function __construct(string $id)
    {
        $uuid = Uuid::fromString($id);
        if ($uuid->getVersion() !== 4) {
            throw new InvalidArgumentException(
                sprintf('Invalid id "%s", valid ID is only UUIDv4', $id)
            );
        }

        $this->id = $uuid->toString(); // valid UUID
    }

    public static function generate() : self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $id) : self
    {
        return new self($id);
    }

    public static function fromStringOrNull(?string $id) : ?self
    {
        if ($id === null) {
            return null;
        }

        return self::fromString($id);
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
}
