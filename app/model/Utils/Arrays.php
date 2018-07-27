<?php

declare(strict_types=1);

namespace Model\Utils;

use Nette\StaticClass;
use function array_reduce;

class Arrays
{
    use StaticClass;

    /**
     * Groups given collection by key provided from $keyFunction
     * @param mixed[] $collection
     * @param bool    $excludeNull Do not add item if keyFunction returns null
     * @return mixed[]
     */
    public static function groupBy(array $collection, callable $keyFunction, bool $excludeNull = false) : array
    {
        $newCollection = [];
        foreach ($collection as $index => $item) {
            $key = $keyFunction($item);
            if ($key === null && $excludeNull === true) {
                continue;
            }

            if (! isset($newCollection[$key])) {
                $newCollection[$key] = [];
            }

            $newCollection[$key][$index] = $item;
        }

        return $newCollection;
    }

    /**
     * @param mixed[] $collection
     * @return mixed
     */
    public static function ungroup(array $collection)
    {
        return array_reduce($collection, 'array_merge', []);
    }
}
