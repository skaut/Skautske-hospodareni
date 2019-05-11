<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use Model\DTO\Payment\Group;
use Model\Payment\BankAccountService;
use Model\Payment\ReadModel\Queries\GetGroupList;
use Model\PaymentService;
use function array_filter;
use function array_keys;
use function array_unique;
use function assert;

final class GroupListPresenter extends BasePresenter
{
    /** @var Factories\IPairButtonFactory */
    private $pairButtonFactory;

    /** @var PaymentService */
    private $groups;

    /** @var BankAccountService */
    private $bankAccounts;

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

        $groupIds       = [];
        $bankAccountIds = [];
        foreach ($groups as $group) {
            assert($group instanceof Group);
            $groupIds[]       = $group->getId();
            $bankAccountIds[] = $group->getBankAccountId();
        }

        $bankAccounts = $this->bankAccounts->findByIds(array_filter(array_unique($bankAccountIds)));

        $groupsPairingSupport = [];
        foreach ($groups as $group) {
            $accountId                             = $group->getBankAccountId();
            $groupsPairingSupport[$group->getId()] = $accountId !== null && $bankAccounts[$accountId]->getToken() !== null;
        }

        $this['pairButton']->setGroups($groupIds);

        $this->template->setParameters([
            'onlyOpen' => $onlyOpen,
            'groups' => $groups,
            'summarizations' => $this->groups->getGroupSummaries($groupIds),
            'groupsPairingSupport' => $groupsPairingSupport,
        ]);
    }

    protected function createComponentPairButton() : Components\PairButton
    {
        return $this->pairButtonFactory->create();
    }
}
