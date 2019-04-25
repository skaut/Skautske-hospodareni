<?php

declare(strict_types=1);

namespace Model\Event\ReadModel;

use stdClass;

class Helpers
{
    /**
     * @param stdClass[] $items
     *
     * @return string[]
     */
    public static function getPairs(array $items) : array
    {
        $pairs = [];

        foreach ($items as $item) {
            $pairs[$item->ID] = $item->DisplayName;
        }

        return $pairs;
    }
}
