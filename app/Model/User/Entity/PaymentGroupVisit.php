<?php

declare(strict_types=1);

namespace App\Model\User\Entity;

use App\Model\Infrastructure\Entity\AbstractIdEntity;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use InvalidArgumentException;

#[Entity(repositoryClass: \App\Model\User\Repository\PaymentGroupVisitRepository::class)]
#[Table(name: 'payment_group_visit')]
#[UniqueConstraint(name: 'payment_group_visit_user_group_unique', columns: ['user_id', 'group_id'])]
#[Index(name: 'payment_group_visit_user_visited_idx', columns: ['user_id', 'visited_at'])]
class PaymentGroupVisit extends AbstractIdEntity
{
    #[Column(name: 'user_id', type: Types::INTEGER, options: ['unsigned' => true])]
    private int $userId;

    #[Column(name: 'group_id', type: Types::INTEGER, options: ['unsigned' => true])]
    private int $groupId;

    #[Column(name: 'visited_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $visitedAt;

    public function __construct(int $userId, int $groupId, ?DateTimeImmutable $visitedAt = null)
    {
        if ($userId < 1) {
            throw new InvalidArgumentException('Payment group visit user_id must be a positive integer.');
        }

        if ($groupId < 1) {
            throw new InvalidArgumentException('Payment group visit group_id must be a positive integer.');
        }

        $this->userId = $userId;
        $this->groupId = $groupId;
        $this->visitedAt = $visitedAt ?? new DateTimeImmutable();
    }

    public function markVisited(?DateTimeImmutable $visitedAt = null): void
    {
        $this->visitedAt = $visitedAt ?? new DateTimeImmutable();
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getVisitedAt(): DateTimeImmutable
    {
        return $this->visitedAt;
    }
}
