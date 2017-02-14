<?php

namespace Model;

interface IChitServiceFactory
{

    public function create(string $name, EventService $events) : ChitService;

}
