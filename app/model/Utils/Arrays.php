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

    /**
     * Because working with references is PITA and there is no nice syntax for multiple comparison functions
     *
     * @param callable[] ...$sortFunctions When first function compares items as same, second is used and so on
     */
    public static function sort(array $array, callable ...$sortFunctions): array
    {
        usort($array, function($a, $b) use ($sortFunctions): int {
            foreach ($sortFunctions as $function) {
                $result = $function($a, $b);

                if ($result !== 0) {
                    return $result;
                }
            }

            return 0;
        });

        return $array;
    }

}
