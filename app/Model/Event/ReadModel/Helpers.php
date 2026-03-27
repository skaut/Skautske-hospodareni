<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel;

use stdClass;

class Helpers
{
    /**
     * @param stdClass[] $items
     *
     * @return string[]
     */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public static function getPairs(array $items): array
    {
        $pairs = [];

        foreach ($items as $item) {
            $pairs[$item->ID] = $item->DisplayName;
        }

        return $pairs;
    }
}
