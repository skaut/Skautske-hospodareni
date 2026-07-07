<?php

declare(strict_types=1);

namespace App\Presentation\Payments\Dashboard;

use App\Components\Factories\Payment\ICreateButtonFactory;
use App\Components\Payment\CreateButton;
use App\Model\Auth\Resources\InvoiceAccess;
use App\Model\DTO\Payment\Group;
use App\Model\Invoice\Repository\InvoiceSequenceRepository;
use App\Model\Payment\Payment\State;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\GetGroupList;
use App\Model\Payment\ReadModel\Queries\RecentlyVisitedGroupsQuery;
use App\Presentation\Payments\PaymentsBasePresenter;

use function array_keys;
use function array_map;
use function array_slice;
use function usort;

final class DashboardPresenter extends PaymentsBasePresenter
{
    public function __construct(
        private readonly InvoiceSequenceRepository $invoiceSequenceRepository,
        private readonly ICreateButtonFactory $createButtonFactory,
        private readonly PaymentService $paymentService,
    ) {
        parent::__construct();
    }

    public function renderDefault(): void
    {
        $readableUnitIds = array_keys($this->unitService->getReadUnits($this->user));
        $currentYear = (int) date('Y');

        /** @var Group[] $allGroups */
        $allGroups = $this->queryBus->handle(new GetGroupList($readableUnitIds, false));
        /** @var Group[] $groups */
        $groups = $this->queryBus->handle(new RecentlyVisitedGroupsQuery((int) $this->getUser()->getId(), $readableUnitIds, 3));
        if ($groups === []) {
            usort(
                $allGroups,
                static fn (Group $first, Group $second): int => $second->getId() <=> $first->getId(),
            );
            $groups = array_slice($allGroups, 0, 3);
        }
        $groupIds = array_map(static fn (Group $group): int => $group->getId(), $groups);
        $groupSummaries = $groupIds === [] ? [] : $this->paymentService->getGroupSummaries($groupIds);
        $groupPaymentCounts = [];
        foreach ($groups as $group) {
            $summaries = $groupSummaries[$group->getId()];
            $completedCount = $summaries[State::COMPLETED]->getCount();
            $groupPaymentCounts[$group->getId()] = [
                'completed' => $completedCount,
                'total' => $completedCount + $summaries[State::PREPARING]->getCount(),
            ];
        }

        $canAccessInvoices = $this->authorizator->isAllowed(InvoiceAccess::ACCESS, null);
        $invoiceSequences = [];
        $editableSequenceIds = [];

        if ($canAccessInvoices) {
            // Invoice sequences — last 2 open for current year
            $invoiceSequences = $this->invoiceSequenceRepository->getOpenGridByUnitsForYear($readableUnitIds, $currentYear, 2);
            $editableSequenceIds = array_map(
                static fn (array $sequence): int => (int) $sequence['id'],
                $this->invoiceSequenceRepository->getGridByUnits($this->getEditableUnits()),
            );
        }

        $this->template->setParameters([
            'unitId' => $this->unitId->toInt(),
            'isEditable' => $this->isEditable,
            'groups' => $groups,
            'groupPaymentCounts' => $groupPaymentCounts,
            'totalGroupCount' => count($allGroups),
            'invoiceSequences' => $invoiceSequences,
            'editableSequenceIds' => $editableSequenceIds,
            'currentYear' => $currentYear,
            'canAccessInvoices' => $canAccessInvoices,
        ]);
    }

    protected function createComponentCreateButton(): CreateButton
    {
        return $this->createButtonFactory->create();
    }
}
