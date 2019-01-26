<?php

declare(strict_types=1);

namespace Model\Travel\ReadModel\Queries;

/**
 * @see TransportTypeHandler
 */
final class TransportTypeQuery
{
    /** @var string */
    private $shortcut;

    public function __construct(string $shortcut)
    {
        $this->shortcut = $shortcut;
    }

    public function getShortcut() : string
    {
        return $this->shortcut;
    }
}
