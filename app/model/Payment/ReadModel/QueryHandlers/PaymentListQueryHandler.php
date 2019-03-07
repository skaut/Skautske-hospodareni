<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Model\DTO\Payment as DTO;
use Model\Payment\Payment;
use Model\Payment\ReadModel\Queries\PaymentListQuery;
use Model\Payment\Repositories\IPaymentRepository;
use function array_map;

final class PaymentListQueryHandler
{
    /** @var IPaymentRepository */
    private $payments;

    public function __construct(IPaymentRepository $payments)
    {
        $this->payments = $payments;
    }

    /**
     * @return DTO\Payment[]
     */
    public function __invoke(PaymentListQuery $query) : array
    {
        $payments = $this->payments->findByGroup($query->getGroupId());

        return array_map(
            function (Payment $payment) {
                return DTO\PaymentFactory::create($payment);
            },
            $payments
        );
    }
}
