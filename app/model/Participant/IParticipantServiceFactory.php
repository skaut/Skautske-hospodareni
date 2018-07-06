<?php

declare(strict_types=1);

namespace Model;

interface IParticipantServiceFactory
{
    public function create(string $name) : ParticipantService;
}
