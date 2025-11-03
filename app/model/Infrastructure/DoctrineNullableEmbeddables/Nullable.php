<?php

declare(strict_types=1);

namespace Model\Infrastructure\DoctrineNullableEmbeddables;

use Attribute;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
#[Attribute]
class Nullable
{
}
