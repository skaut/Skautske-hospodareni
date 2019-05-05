<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Model\DTO\Payment\Person;
use Model\Payment\ReadModel\Queries\MembersWithoutPaymentInGroupQuery;
use Model\Payment\Repositories\IMemberEmailRepository;
use Model\PaymentService;
use Model\Services\Language;
use Skautis\Skautis;
use function in_array;
use function is_array;
use function usort;

final class MembersWithoutPaymentInGroupQueryHandler
{
    /** @var Skautis */
    private $skautis;

    /** @var IMemberEmailRepository */
    private $emails;

    /** @var PaymentService */
    private $paymentService;

    public function __construct(Skautis $skautis, IMemberEmailRepository $emails, PaymentService $paymentService)
    {
        $this->skautis = $skautis;
        $this->emails = $emails;
        $this->paymentService = $paymentService;
    }

    public function __invoke(MembersWithoutPaymentInGroupQuery $query) : array
    {
        $persons = $this->skautis->org->PersonAll([
            'ID_Unit' => $query->getUnitId()->toInt(),
            'OnlyDirectMember' => true
        ]);

        if (! is_array($persons) || empty($persons)) {
            return [];
        }

        $personsWithPayment = $this->paymentService->getPersonsWithActivePayment($query->getGroupId());

        $result = [];
        foreach ($persons as $person) {
            if (in_array($person->ID, $personsWithPayment)) {
                continue;
            }

            $result[] = new Person($person->ID, $person->DisplayName, $this->emails->findByMember($person->ID));
        }

        usort(
            $result,
            function (Person $one, Person $two) {
                return Language::compare($one->getName(), $two->getName());
            }
        );

        return $result;
    }
}
