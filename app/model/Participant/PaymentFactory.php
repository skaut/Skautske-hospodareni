<?php

declare(strict_types=1);

namespace Model\Participant;

use Model\Participant\Payment\Event;
use Model\Utils\MoneyFactory;
use Nette\StaticClass;

final class PaymentFactory
{
    use StaticClass;

    public static function createDefault(int $participantId, Event $event) : Payment
    {
        return new Payment(
            $participantId,
            $event,
            MoneyFactory::zero(),
            MoneyFactory::zero(),
            'N'
        );
    }
}
