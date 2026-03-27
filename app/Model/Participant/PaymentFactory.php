<?php

declare(strict_types=1);

namespace App\Model\Participant;

use App\Model\Participant\Payment\Event;
use App\Model\Utils\MoneyFactory;
use Nette\StaticClass;

final class PaymentFactory
{
    use StaticClass;

    public static function createDefault(int $participantId, Event $event): Payment
    {
        return new Payment(
            PaymentId::generate(),
            $participantId,
            $event,
            MoneyFactory::zero(),
            MoneyFactory::zero(),
            'N',
        );
    }
}
