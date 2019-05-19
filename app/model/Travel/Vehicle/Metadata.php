<?php

declare(strict_types=1);

namespace Model\Travel\Vehicle;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Metadata
{
    /**
     * @ORM\Column(type="datetime_immutable")
     *
     * @var DateTimeImmutable
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $authorName;

    public function __construct(DateTimeImmutable $createdAt, string $authorName)
    {
        $this->createdAt  = $createdAt;
        $this->authorName = $authorName;
    }

    public function getCreatedAt() : DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAuthorName() : string
    {
        return $this->authorName;
    }

    public function equals(Metadata $metadata) : bool
    {
        $dateTimeFormat = 'Y-m-d H:i:s';

        return $this->authorName === $metadata->authorName
            && $this->createdAt->format($dateTimeFormat) === $metadata->createdAt->format($dateTimeFormat);
    }
}
