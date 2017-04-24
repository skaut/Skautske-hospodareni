<?php

namespace Model\Travel;

use Doctrine\Common\Collections\ArrayCollection;
use Model\Travel\Command\Travel;

class Command
{

    /** @var int */
    private $id;

    /** @var Vehicle */
    private $vehicle;

    /** @var ArrayCollection|Travel[] */
    private $travels;

}
