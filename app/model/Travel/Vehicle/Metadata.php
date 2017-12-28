<?php

namespace Model\Travel\Vehicle;

class Metadata
{

    /** @var \DateTimeImmutable */
    private $createdAt;

    /** @var string */
    private $authorName;

    public function __construct(\DateTimeImmutable $createdAt, string $authorName)
    {
        $this->createdAt = $createdAt;
        $this->authorName = $authorName;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    public function equals(Metadata $metadata): bool
    {
        return $this->authorName === $metadata->authorName
            && $this->createdAt == $metadata->createdAt;
    }

}
