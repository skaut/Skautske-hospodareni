<?php

namespace Model\Travel\Command;

use Model\Travel\Command;

class Travel
{

    /** @var int */
    private $id;

    /** @var string */
    private $startPlace;

    /** @var string */
    private $endPlace;

    /** @var float */
    private $distance;

    /** @var Command @internal */
    private $command;

}
