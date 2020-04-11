<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use Model\PaymentService;
use function array_keys;

class JournalPresenter extends BasePresenter
{
    /** @var PaymentService */
    private $model;

    public function __construct(PaymentService $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    public function renderDefault(int $groupId) : void
    {
        if (! $this->isEditable) {
            $this->setView('accessDenied');

            return;
        }
        $group = $this->model->getGroup($groupId);
        $year  = $this->model->getRegistrationYear($group->getSkautisId());
        if ($year === null) {
            $this->flashMessage('Registrace nebyla nalezena', 'danger');
            $this->redirect('GroupList:');
        }
        $units = $this->unitService->getAllUnder($this->unitId->toInt());

        $changes      = [];
        $changeExists = false;
        foreach (array_keys($units) as $unitId) {
            $uch              = $this->model->getJournalChangesAfterRegistration($unitId, $year);
            $changeExists     = $changeExists || (empty($uch['add']) && $uch['remove']);
            $changes[$unitId] = $uch;
        }
        $this->template->setParameters([
            'year'=> $year,
            'units' => $units,
            'changes' => $changes,
            'changeExists' => $changeExists,
        ]);
    }
}
