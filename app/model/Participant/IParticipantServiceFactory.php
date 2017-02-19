<?php

namespace Model;

interface IParticipantServiceFactory
{

    public function create(string $name) : ParticipantService;

}
