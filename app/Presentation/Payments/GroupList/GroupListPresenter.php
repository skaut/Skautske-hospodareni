<?php

declare(strict_types=1);

namespace App\Presentation\Payments\GroupList;

use App\Components\Factories\Payment\ICreateButtonFactory;
use App\Components\Factories\Payment\IPairButtonFactory;
use App\Components\Payment\CreateButton;
use App\Components\Payment\GroupProgress;
use App\Components\Payment\PairButton;
use App\Model\DTO\Payment\Group;
use App\Model\Payment\BankAccountService;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\GetGroupList;
use App\Model\Payment\Summary;
use App\Model\Unit\ReadModel\Queries\UnitQuery;
use App\Model\Unit\UnitNotFound;
use App\Presentation\Payments\PaymentsBasePresenter;
use Nette\Application\UI\Multiplier;

use function array_filter;
use function array_keys;
use function array_unique;
use function assert;

final class GroupListPresenter extends PaymentsBasePresenter
{
    /** @var array<int, array<string, Summary>> */
    private array $summaries;

    public function __construct(
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
        $bankAccountIds = [];
        $unitNamesByGroup = [];

        foreach ($groups as $group) {
            assert($group instanceof Group);
            $groupIds[] = $group->getId();
            $bankAccountIds[] = $group->getBankAccountId();

            foreach ($group->getUnitIds() as $unitId) {
                try {
                    $unitNamesByGroup[$group->getId()] = [$this->queryBus->handle(new UnitQuery($unitId))->getDisplayName()];
                } catch (UnitNotFound) {
                }
            }
        }

        $bankAccounts = $this->bankAccounts->findByIds(array_filter(array_unique($bankAccountIds)));

        $groupsPairingSupport = [];
        foreach ($groups as $group) {
            $accountId = $group->getBankAccountId();
            $groupsPairingSupport[$group->getId()] = $accountId !== null && $bankAccounts[$accountId]->getToken() !== null;
        }

        $this['pairButton']->setGroups($groupIds);

        $this->summaries = $this->groups->getGroupSummaries($groupIds);

        $this->template->setParameters([
            'onlyOpen' => $onlyOpen,
            'groups' => $groups,
            'groupsPairingSupport' => $groupsPairingSupport,
            'groupUnits' => $unitNamesByGroup,
        ]);
    }

    protected function createComponentPairButton(): PairButton
    {
        $control = $this->pairButtonFactory->create();
        $control->setCss('button', 'btn btn-success dropdown-toggle ms-2');

        return $control;
    }

    protected function createComponentCreateButton(): CreateButton
    {
        $control = $this->createButtonFactory->create();

        return $control;
    }

    protected function createComponentProgress(): Multiplier
    {
        return new Multiplier(function (string $groupId): GroupProgress {
            return new GroupProgress($this->summaries[(int) $groupId]);
        });
    }
}
