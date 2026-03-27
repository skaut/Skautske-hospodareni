<?php

declare(strict_types=1);

namespace App\Presentation\Payments\Dashboard;

use App\Components\Payment\CreateButton;
use App\Components\Factories\Payment\ICreateButtonFactory;
use App\Model\DTO\Payment\Group;
use App\Model\Invoice\Repository\InvoiceSequenceRepository;
use App\Model\Payment\ReadModel\Queries\GetGroupList;
use App\Presentation\Payments\PaymentsBasePresenter;

use function array_keys;
use function array_map;
use function array_slice;

final class DashboardPresenter extends PaymentsBasePresenter
{
    public function __construct(
        private readonly InvoiceSequenceRepository $invoiceSequenceRepository,
        private readonly ICreateButtonFactory $createButtonFactory,
    ) {
        parent::__construct();
    }

    public function renderDefault(): void
    {
        $readableUnitIds = array_keys($this->unitService->getReadUnits($this->user));
        $currentYear = (int) date('Y');

        // Payment groups — last 2 open
        /** @var Group[] $allGroups */
        $allGroups = $this->queryBus->handle(new GetGroupList($readableUnitIds, true));
        $groups = array_slice($allGroups, 0, 2);

        // Invoice sequences — last 2 open for current year
        $invoiceSequences = $this->invoiceSequenceRepository->getOpenGridByUnitsForYear($readableUnitIds, $currentYear, 2);
        $editableSequenceIds = array_map(
            static fn (array $sequence): int => (int) $sequence['id'],
            $this->invoiceSequenceRepository->getGridByUnits($this->getEditableUnits()),
        );

        $this->template->setParameters([
            'unitId' => $this->unitId->toInt(),
            'isEditable' => $this->isEditable,
            'groups' => $groups,
            'totalGroupCount' => count($allGroups),
            'invoiceSequences' => $invoiceSequences,
            'editableSequenceIds' => $editableSequenceIds,
            'currentYear' => $currentYear,
        ]);
    }

    protected function createComponentCreateButton(): CreateButton
    {
        return $this->createButtonFactory->create();
    }
}
