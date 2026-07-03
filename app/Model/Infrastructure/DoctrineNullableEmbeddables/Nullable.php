<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\DoctrineNullableEmbeddables;

use Attribute;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
#[Attribute]
class Nullable
{
}
