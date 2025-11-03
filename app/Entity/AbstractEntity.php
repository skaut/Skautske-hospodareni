<?php

declare(strict_types=1);

namespace Entity;

use ArrayAccess;
use Doctrine\ORM\Mapping\ChangeTrackingPolicy; // Import pro atribut
use Doctrine\ORM\Mapping\MappedSuperclass; // Import pro atribut
use InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use function in_array;

/** @implements ArrayAccess<string, mixed> */
#[MappedSuperclass]
#[ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
abstract class AbstractEntity implements ArrayAccess
{
    private ?PropertyAccessorInterface $arrayAccessor = null;

    /** @param iterable<string, mixed> $values */
    public function updateOnly(iterable $values, string ...$keys): static
    {
        if (empty($keys)) {
            return $this;
        }

        $accessor = $this->getArrayAccessor();

        foreach ($values as $key => $value) {
            if ($key === 'id' || in_array($key, $keys, true) === false) {
                continue;
            }

            if (! $accessor->isWritable($this, $key)) {
                continue;
            }

            $accessor->setValue($this, $key, $value);
        }

        return $this;
    }

    protected function getArrayAccessor(): PropertyAccessorInterface
    {
        return $this->arrayAccessor ??= PropertyAccess::createPropertyAccessorBuilder()
            ->disableMagicCall()
            ->getPropertyAccessor();
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->getArrayAccessor()->isReadable($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->getArrayAccessor()->getValue($this, $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            throw new InvalidArgumentException('Cannot set empty offset on entity');
        }

        $this->getArrayAccessor()->setValue($this, $offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->getArrayAccessor()->setValue($this, $offset, null);
    }
}
