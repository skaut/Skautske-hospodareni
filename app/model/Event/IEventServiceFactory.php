<?php

declare(strict_types=1);

namespace Model;

interface IEventServiceFactory
{
    public function create(string $name) : EventService;
}
