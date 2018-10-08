<?php

declare(strict_types=1);

namespace Model\Travel\Vehicle;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Metadata
{
    /**
     * @var \DateTimeImmutable
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $authorName;

    public function __construct(\DateTimeImmutable $createdAt, string $authorName)
    {
        $this->createdAt  = $createdAt;
        $this->authorName = $authorName;
    }

    public function getCreatedAt() : \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAuthorName() : string
    {
        return $this->authorName;
    }

    public function equals(Metadata $metadata) : bool
    {
        return $this->authorName === $metadata->authorName
            && $this->createdAt === $metadata->createdAt;
    }
}
