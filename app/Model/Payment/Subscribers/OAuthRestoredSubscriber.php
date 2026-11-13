<?php

declare(strict_types=1);

namespace App\Model\Payment\Subscribers;

use App\Model\Payment\DomainEvents\OAuthWasRestored;
use App\Model\Payment\Mailing\PaymentEmailQueue;
use App\Model\Payment\Repositories\IGroupRepository;

/** Confirmations that waited for the sender of a group go out as soon as Google accepts it again. */
final class OAuthRestoredSubscriber
{
    public function __construct(private IGroupRepository $groups, private PaymentEmailQueue $emailQueue)
    {
    }

    public function __invoke(OAuthWasRestored $event): void
    {
        foreach ($this->groups->findByOAuth($event->getOAuthId()) as $group) {
            $this->emailQueue->releaseWaitingForSender((int) $group->getId());
        }
    }
}
