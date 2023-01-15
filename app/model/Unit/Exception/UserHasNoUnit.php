<?php

declare(strict_types=1);

namespace Model\Unit;

use Exception;

use function sprintf;

final class UserHasNoUnit extends Exception
{
    public static function fromLoginId(string|null $loginId): self
    {
        return new self(sprintf('User "%s" has no unit', $loginId ?? ''));
    }
}
