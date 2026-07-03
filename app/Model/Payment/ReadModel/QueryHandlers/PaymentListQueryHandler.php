<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\DTO\Payment as DTO;
use App\Model\Payment\Payment;
use App\Model\Payment\ReadModel\Queries\PaymentListQuery;
use App\Model\Payment\Repositories\IPaymentRepository;

use function array_map;

final class PaymentListQueryHandler
{
    public function __construct(private IPaymentRepository $payments)
    {
    }

    /** @return DTO\Payment[] */
    public function __invoke(PaymentListQuery $query): array
    {
        $payments = $this->payments->findByGroup($query->getGroupId());

        return array_map(
            function (Payment $payment) {
                return DTO\PaymentFactory::create($payment);
            },
            $payments,
        );
    }
}
