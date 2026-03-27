<?php

declare(strict_types=1);

namespace App\Presentation\Payments\People;

use App\Model\Payment\PaymentService;
use App\Presentation\Payments\PaymentsBasePresenter as BasePresenter;

final class PeoplePresenter extends BasePresenter
{
    public function __construct(private PaymentService $payments)
    {
        parent::__construct();
    }

    /** @param null $unitId - required by forwarded registration presenter */
    public function actionDefault(int $id, ?int $unitId = null, bool $directMemberOnly = true): void
    {
        $group = $this->payments->getGroup($id);

        if ($group === null || ! $this->canEditGroup($group)) {
            $this->flashMessage('Neoprávněný přístup ke skupině.', 'danger');
            $this->redirect(':Payments:GroupList:');
        }

        if ($group->getType() === 'registration') {
            $params = ['id' => $id];

            if ($unitId !== null) {
                $params['unitId'] = $unitId;
            }

            $this->forward(':Payments:RegistrationAddMembers:', $params);
        }

        $params = [
            'id' => $id,
            'directMemberOnly' => $directMemberOnly,
        ];

        if ($unitId !== null) {
            $params['unitId'] = $unitId;
        }

        $this->forward(':Payments:Payment:massAdd', $params);
    }
}
