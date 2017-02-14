<?php

namespace Model;

interface IChitServiceFactory
{

    public function create(string $name) : ChitService;

}
