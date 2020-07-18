<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\GroupProgress;
use Model\DTO\Payment\Group;
use Model\Payment\BankAccountService;
use Model\Payment\ReadModel\Queries\GetGroupList;
use Model\Payment\Summary;
use Model\PaymentService;
use Model\Unit\ReadModel\Queries\UnitQuery;
use Nette\Application\UI\Multiplier;
use function array_filter;
use function array_keys;
use function array_map;
use function array_unique;
use function assert;

final class GroupListPresenter extends BasePresenter
{
    private Factories\IPairButtonFactory $pairButtonFactory;

    private PaymentService $groups;

    private BankAccountService $bankAccounts;

    /** @var array<int, array<string, Summary>> */
    private array $summaries;

    public function __construct(
        Factories\IPairButtonFactory $pairButtonFactory,
        PaymentService $groups,
        BankAccountService $bankAccounts
    ) {
        parent::__construct();
        $this->pairButtonFactory = $pairButtonFactory;
        $this->groups            = $groups;
        $this->bankAccounts      = $bankAccounts;
    }

    public function actionDefault(bool $onlyOpen = true) : void
    {
        $groups = $this->queryBus->handle(
            new GetGroupList(array_keys($this->unitService->getReadUnits($this->user)), $onlyOpen)
        );

        $groupIds         = [];
        $bankAccountIds   = [];
        $unitNamesByGroup = [];

        foreach ($groups as $group) {
            assert($group instanceof Group);
            $groupIds[]       = $group->getId();
            $bankAccountIds[] = $group->getBankAccountId();

            $unitNamesByGroup[$group->getId()] = array_map(
                function (int $unitId) : string {
                    return $this->queryBus->handle(new UnitQuery($unitId))->getDisplayName();
                },
                $group->getUnitIds()
            );
        }

        $bankAccounts = $this->bankAccounts->findByIds(array_filter(array_unique($bankAccountIds)));

        $groupsPairingSupport = [];
        foreach ($groups as $group) {
            $accountId                             = $group->getBankAccountId();
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

    protected function createComponentPairButton() : Components\PairButton
    {
        return $this->pairButtonFactory->create();
    }

    protected function createComponentProgress() : Multiplier
    {
        return new Multiplier(function (string $groupId) : GroupProgress {
            return new GroupProgress($this->summaries[(int) $groupId]);
        });
    }
}
