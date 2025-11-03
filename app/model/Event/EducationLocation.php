<?php

declare(strict_types=1);

namespace Model\Event;

use Nette\SmartObject;

/**
 * @property SkautisEducationLocationId $id
 * @property string                     $name
 * @property string                     $displayName
 */
class EducationLocation
{
    use SmartObject;

    public function __construct(
        private SkautisEducationLocationId $id,
        private string $name,
        private string $displayName,
    ) {
    }

    public function getId(): SkautisEducationLocationId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }
}
