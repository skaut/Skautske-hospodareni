<?php

declare(strict_types=1);

namespace App\Presentation\Payments\RegistrationJournal;

use App\Model\Payment\PaymentService;
use App\Presentation\Payments\PaymentsBasePresenter as BasePresenter;
use RuntimeException;

use function array_keys;

final class RegistrationJournalPresenter extends BasePresenter
{
    public function __construct(private PaymentService $model)
    {
        parent::__construct();
    }

    public function renderDefault(int $groupId): void
    {
        if (! $this->isEditable) {
            $this->setView('accessDenied');

            return;
        }

        $group = $this->model->getGroup($groupId) ?? throw new RuntimeException('Platební skupina nebyla nalezena.');
        $year = $this->model->getRegistrationYear((int) $group->getSkautisId());
        if ($year === null) {
            $this->flashMessage('Registrace nebyla nalezena', 'danger');
            $this->redirect(':Payments:GroupList:');
        }

        $units = $this->unitService->getAllUnder($this->unitId->toInt());

        $changes = [];
        $changeExists = false;
        foreach (array_keys($units) as $unitId) {
            $uch = $this->model->getJournalChangesAfterRegistration($unitId, $year);
            $changeExists = $changeExists || (empty($uch['add']) && $uch['remove']);
            $changes[$unitId] = $uch;
        }

        $this->template->setParameters([
            'year' => $year,
            'units' => $units,
            'changes' => $changes,
            'changeExists' => $changeExists,
        ]);
    }
}
