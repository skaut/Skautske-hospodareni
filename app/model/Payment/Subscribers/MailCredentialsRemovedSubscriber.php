<?php

declare(strict_types=1);

namespace Model\Payment\Subscribers;

use Model\Payment\DomainEvents\OAuthWasRemoved;
use Model\Payment\Repositories\IGroupRepository;

final class MailCredentialsRemovedSubscriber
{
    /** @var IGroupRepository */
    private $groups;

    public function __construct(IGroupRepository $groups)
    {
        $this->groups = $groups;
    }

    public function __invoke(OAuthWasRemoved $event) : void
    {
        foreach ($this->groups->findByOAuth($event->getOAuthId()) as $group) {
            $group->resetOAuth();
            $this->groups->save($group);
        }
    }
}
