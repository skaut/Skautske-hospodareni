<?php

declare(strict_types=1);

namespace Model\Participant;

use Model\Event\SkautisEventId;
use Model\Utils\MoneyFactory;
use Nette\StaticClass;

final class PaymentFactory
{
    use StaticClass;

    public static function createDefault(int $participantId, SkautisEventId $actionId) : Payment
    {
        return new Payment(
            $participantId,
            $actionId,
            MoneyFactory::zero(),
            MoneyFactory::zero(),
            'N'
        );
    }
}
