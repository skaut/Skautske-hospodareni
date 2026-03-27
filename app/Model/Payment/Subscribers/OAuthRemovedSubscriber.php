<?php

declare(strict_types=1);

namespace App\Model\Payment\Subscribers;

use App\Model\Payment\DomainEvents\OAuthWasRemoved;
use App\Model\Payment\Repositories\IGroupRepository;

final class OAuthRemovedSubscriber
{
    public function __construct(private IGroupRepository $groups)
    {
    }

    public function __invoke(OAuthWasRemoved $event): void
    {
        foreach ($this->groups->findByOAuth($event->getOAuthId()) as $group) {
            $group->resetOAuth();
            $this->groups->save($group);
        }
    }
}
