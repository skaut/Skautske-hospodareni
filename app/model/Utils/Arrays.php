<?php

namespace Model\Utils;


use Nette\StaticClass;

class Arrays
{

    use StaticClass;

    /**
     * Groups given collection by key provided from $keyFunction
     * @param array $collection
     * @param callable $keyFunction
     * @param bool $excludeNull Do not add item if keyFunction returns null
     * @return array
     */
    public static function groupBy(array $collection, callable $keyFunction, bool $excludeNull = FALSE): array
    {
        $newCollection = [];
        foreach($collection as $index => $item) {
            $key = $keyFunction($item);
            if ($key === NULL && $excludeNull === TRUE) {
                continue;
            }

            if(!isset($newCollection[$key])) {
                $newCollection[$key] = [];
            }

            $newCollection[$key][$index] = $item;
        }

        return $newCollection;
    }

    public static function ungroup(array $collection)
    {
        return array_reduce($collection, "array_merge", []);
    }

}
