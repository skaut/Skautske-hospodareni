<?php

declare(strict_types=1);

namespace Model\Infrastructure;

use Consistence\Doctrine\Enum\EnumPostLoadEntityListener;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Kdyby\Events\Subscriber;

class EnumListener implements Subscriber
{
    /** @var EnumPostLoadEntityListener */
    private $listener;

    public function getSubscribedEvents()
    {
        return ['postLoad'];
    }

    public function __construct(EnumPostLoadEntityListener $listener)
    {
        $this->listener = $listener;
    }

    public function postLoad(LifecycleEventArgs $args) : void
    {
        $this->listener->postLoad($args);
    }
}
