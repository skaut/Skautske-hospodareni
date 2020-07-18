<?php

declare(strict_types=1);

namespace Model\Payment\Subscribers;

use Model\Payment\DomainEvents\MailCredentialsWasRemoved;
use Model\Payment\Repositories\IGroupRepository;

final class MailCredentialsRemovedSubscriber
{
    private IGroupRepository $groups;

    public function __construct(IGroupRepository $groups)
    {
        $this->groups = $groups;
    }

    public function __invoke(MailCredentialsWasRemoved $event) : void
    {
        foreach ($this->groups->findBySmtp($event->getCredentialsId()) as $group) {
            $group->resetSmtp();
            $this->groups->save($group);
        }
    }
}
