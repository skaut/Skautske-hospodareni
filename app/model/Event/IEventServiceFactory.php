<?php

namespace Model;

interface IEventServiceFactory
{

    public function create(string $name): EventService;

}
