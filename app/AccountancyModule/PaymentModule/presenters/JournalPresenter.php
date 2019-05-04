<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use Model\PaymentService;
use function array_keys;
use function date;

class JournalPresenter extends BasePresenter
{
    /** @var PaymentService */
    private $model;

    public function __construct(PaymentService $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    public function renderDefault(int $unitId, ?int $year = null) : void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Nemáte oprávnění přistupovat ke správě emailů', 'danger');
            $this->redirect('Payment:default');
        }

        if ($year === null) {
            $year = date('Y');
        }
        $units = $this->unitService->getAllUnder($this->unitId->toInt());

        $changes      = [];
        $changeExists = false;
        foreach (array_keys($units) as $unitId) {
            $uch              = $this->model->getJournalChangesAfterRegistration($unitId, (int) $year);
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
