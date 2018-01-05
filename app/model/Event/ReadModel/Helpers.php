<?php

declare(strict_types=1);

namespace Model\Event\ReadModel;

class Helpers
{

    /**
     * @param \stdClass[] $items
     * @return array<int,string>
     */
    public static function getPairs(array $items): array
    {
        $pairs = [];

        foreach ($items as $item) {
            $pairs[$item->ID] = $item->DisplayName;
        }

        return $pairs;
    }

}
