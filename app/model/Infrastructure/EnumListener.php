<?php

declare(strict_types=1);

namespace Model\Infrastructure;

use Consistence\Doctrine\Enum\EnumPostLoadEntityListener;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Kdyby\Events\Subscriber;

class EnumListener implements Subscriber
{
    private EnumPostLoadEntityListener $listener;

    public function __construct(EnumPostLoadEntityListener $listener)
    {
        $this->listener = $listener;
    }

    /** @return string[] */
    public function getSubscribedEvents() : array
    {
        return ['postLoad'];
    }

    public function postLoad(LifecycleEventArgs $args) : void
    {
        $this->listener->postLoad($args);
    }
}
