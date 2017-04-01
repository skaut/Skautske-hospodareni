<?php

namespace Model\Payment\Payment;

use Consistence\Enum\Enum;

class State extends Enum
{

    public const CANCELED = 'canceled';
    public const COMPLETED = 'completed';
    public const PREPARING = 'preparing';

}
