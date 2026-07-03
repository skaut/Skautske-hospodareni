<?php

declare(strict_types=1);

namespace App\Presentation\Payments\Participants;

use App\Model\Payment\PaymentService;
use App\Presentation\Payments\PaymentsBasePresenter as BasePresenter;

final class ParticipantsPresenter extends BasePresenter
{
    public function __construct(private PaymentService $payments)
    {
        parent::__construct();
    }

    /** @param null $unitId - required by forwarded education presenter */
    public function actionDefault(int $id, ?int $unitId = null): void
    {
        $group = $this->payments->getGroup($id);

        if ($group === null || ! $this->canEditGroup($group)) {
            $this->flashMessage('Neoprávněný přístup ke skupině.', 'danger');
            $this->redirect(':Payments:GroupList:');
        }

        $params = ['id' => $id];

        if ($unitId !== null) {
            $params['unitId'] = $unitId;
        }

        match ($group->getType()) {
            'camp' => $this->forward(':Payments:CampAddParticipants:', $params),
            'event' => $this->forward(':Payments:EventAddParticipants:', $params),
            'education' => $this->forward(':Payments:EducationAddParticipants:', $params),
            default => $this->redirect(':Payments:Payment:default', ['id' => $id]),
        };
    }
}
