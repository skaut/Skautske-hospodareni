<?php

namespace Model;

interface IChitServiceFactory
{

    public function create(): ChitService;

}
