<?php

declare(strict_types=1);

namespace App\Presentation\Payments\GroupList;

use App\Components\DataGrid;
use App\Components\Factories\Payment\ICreateButtonFactory;
use App\Components\Factories\Payment\IPairButtonFactory;
use App\Components\Grids\GridFactory;
use App\Components\Payment\CreateButton;
use App\Components\Payment\PairButton;
use App\Model\DTO\Payment\Group;
use App\Model\Payment\BankAccountService;
use App\Model\Payment\Payment\State;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\GetGroupList;
use App\Model\Payment\Summary;
use App\Model\Unit\ReadModel\Queries\UnitQuery;
use App\Model\Unit\UnitNotFound;
use App\Presentation\Payments\PaymentsBasePresenter;
use DateTimeImmutable;
use LogicException;
use Nette\Application\UI\Multiplier;

use function array_filter;
use function array_keys;
use function array_reduce;
use function array_unique;
use function array_values;
use function ceil;
use function implode;
use function in_array;

final class GroupListPresenter extends PaymentsBasePresenter
{
    /** @var array<int, array{id: int, name: string, units: string, progress: int, completedCount: int, totalCount: int, completedAmount: float, totalAmount: float, state: string, createdAt: DateTimeImmutable|null, canEdit: bool, canClone: bool, canPair: bool, pairLink: string|null}> */
    private array $gridRows = [];

    /** @var int[] */
    private array $pairableGroupIds = [];

    public function __construct(
        private readonly GridFactory $gridFactory,
        private IPairButtonFactory $pairButtonFactory,
        private readonly ICreateButtonFactory $createButtonFactory,
        private PaymentService $groups,
        private BankAccountService $bankAccounts,
    ) {
        parent::__construct();
    }

    public function actionDefault(bool $onlyOpen = true): void
    {
        $groups = $this->queryBus->handle(
            new GetGroupList(array_keys($this->unitService->getReadUnits($this->user)), $onlyOpen),
        );

        $groupIds = [];
        $editableGroupIds = [];
        $bankAccountIds = [];
        $unitNamesByGroup = [];
        $unitNameCache = [];

        foreach ($groups as $group) {
            if (! $group instanceof Group) {
                throw new LogicException('Assertion failed.');
            }
            $groupIds[] = $group->getId();
            $bankAccountIds[] = $group->getBankAccountId();

            if ($this->canEditGroup($group)) {
                $editableGroupIds[] = $group->getId();
            }

            foreach ($group->getUnitIds() as $unitId) {
                try {
                    $unitNameCache[$unitId] ??= $this->queryBus->handle(new UnitQuery($unitId))->getDisplayName();
                    $unitNamesByGroup[$group->getId()][] = $unitNameCache[$unitId];
                } catch (UnitNotFound) {
                }
            }
        }

        $bankAccounts = $this->bankAccounts->findByIds(array_filter(array_unique($bankAccountIds)));

        $groupsPairingSupport = [];
        foreach ($groups as $group) {
            $accountId = $group->getBankAccountId();
            $groupsPairingSupport[$group->getId()] = $accountId !== null
                && isset($bankAccounts[$accountId])
                && $bankAccounts[$accountId]->getToken() !== null;
        }

        $this->pairableGroupIds = array_values(array_filter(
            $editableGroupIds,
            static fn (int $groupId): bool => $groupsPairingSupport[$groupId],
        ));

        $this['pairButton']->setGroups($editableGroupIds);
        $summaries = $this->groups->getGroupSummaries($groupIds);

        foreach ($groups as $group) {
            $groupId = $group->getId();
            $groupSummaries = $summaries[$groupId] ?? [];
            $allPayments = array_reduce(
                $groupSummaries,
                static fn (Summary $total, Summary $summary): Summary => $total->add($summary),
                new Summary(0, 0),
            );
            $completedPayments = $groupSummaries[State::COMPLETED] ?? new Summary(0, 0);
            $canEdit = in_array($groupId, $editableGroupIds, true);
            $canPair = in_array($groupId, $this->pairableGroupIds, true);

            $this->gridRows[] = [
                'id' => $groupId,
                'name' => $group->getName(),
                'units' => implode(', ', array_unique($unitNamesByGroup[$groupId] ?? [])),
                'progress' => $allPayments->getCount() === 0
                    ? 0
                    : (int) ceil($completedPayments->getCount() / $allPayments->getCount() * 100),
                'completedCount' => $completedPayments->getCount(),
                'totalCount' => $allPayments->getCount(),
                'completedAmount' => $completedPayments->getAmount(),
                'totalAmount' => $allPayments->getAmount(),
                'state' => $group->getState(),
                'createdAt' => $group->getCreatedAt(),
                'canEdit' => $canEdit,
                'canClone' => $this->isEditable && $canEdit,
                'canPair' => $canPair,
                'pairLink' => $canPair ? $this->link('rowPairButton-'.$groupId.'-pair!') : null,
            ];
        }

        $this->template->setParameters([
            'onlyOpen' => $onlyOpen,
        ]);
    }

    protected function createComponentPairButton(): PairButton
    {
        $control = $this->pairButtonFactory->create();
        $control->setCss('button', 'btn btn-primary dropdown-toggle ms-2');

        return $control;
    }

    protected function createComponentCreateButton(): CreateButton
    {
        $control = $this->createButtonFactory->create();

        return $control;
    }

    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->gridFactory->createSimpleGrid(__DIR__.'/grid.latte');

        $grid->addColumnText('name', 'Název')
            ->setSortable();
        $grid->addColumnText('units', 'Jednotky')
            ->setSortable();
        $grid->addColumnNumber('progress', 'Zaplaceno')
            ->setSortable();
        $grid->addColumnDateTime('createdAt', 'Vytvořeno')
            ->setFormat('j. n. Y')
            ->setSortable();
        $grid->addColumnText('state', 'Stav')
            ->setSortable();
        $grid->addColumnText('actions', 'Akce')
            ->addCellAttributes(['class' => 'text-end text-nowrap']);

        $grid->addFilterText('search', '', ['name', 'units'])
            ->setPlaceholder('Hledat skupinu...');
        $grid->setDefaultSort(['createdAt' => DataGrid::SORT_DESC]);
        $grid->setDataSource($this->gridRows);

        return $grid;
    }

    protected function createComponentRowPairButton(): Multiplier
    {
        return new Multiplier(function (string $groupId): PairButton {
            $control = $this->pairButtonFactory->create();
            $id = (int) $groupId;
            $control->setGroups(in_array($id, $this->pairableGroupIds, true) ? [$id] : []);

            return $control;
        });
    }
}
