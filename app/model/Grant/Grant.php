<?php

declare(strict_types=1);

namespace Model\Grant;

use Nette\SmartObject;

/**
 * @property-read SkautisGrantId $id
 */
class Grant
{
    use SmartObject;

    public function __construct(
        private SkautisGrantId $id,
    ) {
    }

    public function getId(): SkautisGrantId
    {
        return $this->id;
    }
}
