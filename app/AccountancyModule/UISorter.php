<?php

declare(strict_types=1);

namespace App\AccountancyModule;

use Model\Services\Language;
use Symfony\Component\PropertyAccess\PropertyAccess;
use function is_string;

final class UISorter
{
    /** @var callable(object $object): mixed */
    private $accessor;

    public function __construct(string $fieldName)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $this->accessor = function (object $object) use ($accessor, $fieldName) {
            return $accessor->getValue($object, $fieldName);
        };
    }

    /**
     * Returns 1, 0 or -1 as required by sorting functions
     */
    public function __invoke(object $first, object $second) : int
    {
        $firstField  = ($this->accessor)($first);
        $secondField = ($this->accessor)($second);

        if (is_string($firstField) && is_string($secondField)) {
            return Language::compare($firstField, $secondField);
        }

        return $firstField <=> $secondField;
    }
}
