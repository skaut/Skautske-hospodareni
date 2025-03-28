<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use Model\BankService;
use Model\BankTimeLimit;
use Model\BankTimeout;
use Model\BankWrongTokenAccount;
use Model\DTO\Payment\Group;
use Model\DTO\Payment\PairingResult;
use Model\Google\InvalidOAuth;
use Model\Payment\BankAccountService;
use Model\PaymentService;

use function array_filter;
use function array_map;
use function assert;
use function bdump;
use function count;

class PairButton extends BaseControl
{
    public const TIMEOUT_MESSAGE    = 'Nepodařilo se připojit k bankovnímu serveru. Zkontrolujte svůj API token pro přístup k účtu.';
    public const TIME_LIMIT_MESSAGE = 'Mezi dotazy na bankovnictví musí být prodleva 1 minuta!';

    /** @var int[] */
    private array $groupIds = [];

    public function __construct(private PaymentService $payments, private BankService $model, private BankAccountService $bankAccounts)
    {
    }

    public function handlePair(): void
    {
        $this->pair();
    }

    /**
     * Select groups to pair
     *
     * @param int[] $groupIds
     */
    public function setGroups(array $groupIds): void
    {
        $this->groupIds = $groupIds;
    }

    public function render(): void
    {
        $this->template->setParameters([
            'canPair'     => $this->canPair(),
            'groupsCount' => count($this->groupIds),
        ]);
        $this->template->setFile(__DIR__ . '/templates/PairButton.latte');
        $this->template->render();
    }

    public function renderLight(): void
    {
        $this->template->setParameters(['style' => 'light']);
        $this->render();
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();

        $form->addText('days', 'Počet dní', 2, 2)
            ->setDefaultValue(BankService::DAYS_BACK_DEFAULT)
            ->setRequired('Musíte vyplnit počet dní')
            ->addRule($form::MIN, 'Musíte zadat alespoň kladný počet dní', 1)
            ->setHtmlType('number');
        $form->addSubmit('pair', 'Párovat');

        $form->onSuccess[] = function ($form, $values): void {
            $this->pair((int) $values->days);
        };
        $this->redrawControl('form');

        return $form;
    }

    private function canPair(): bool
    {
        if (empty($this->groupIds)) {
            return false;
        }

        $groups         = $this->payments->findGroupsByIds($this->groupIds);
        $bankAccountIds = array_map(
            function (Group $g) {
                return $g->getBankAccountId();
            },
            $groups,
        );
        $bankAccountIds = array_filter($bankAccountIds);

        $bankAccounts = $this->bankAccounts->findByIds($bankAccountIds);

        foreach ($bankAccounts as $account) {
            if ($account->getToken() !== null) {
                return true;
            }
        }

        return false;
    }

    private function pair(int|null $daysBack = null): void
    {
        try {
            $pairingResults = $this->model->pairAllGroups($this->groupIds, $daysBack);
            foreach ($pairingResults as $p) {
                assert($p instanceof PairingResult);
                $this->flashMessage($p->getMessage(), $p->getCount() > 0 ? 'success' : 'info');
            }
        } catch (BankTimeout) {
            $this->flashMessage(self::TIMEOUT_MESSAGE, 'danger');
            bdump(self::TIMEOUT_MESSAGE);
        } catch (BankTimeLimit) {
            $this->flashMessage(self::TIME_LIMIT_MESSAGE, 'danger');
            bdump(self::TIME_LIMIT_MESSAGE);
        } catch (BankWrongTokenAccount $e) {
            $this->flashMessage($this->wrongTokenAccountMessage($e), 'danger');
            bdump($this->wrongTokenAccountMessage($e));
        } catch (InvalidOAuth $exc) {
            $this->flashMessage($exc->getExplainedMessage(), 'danger');
        }

        $this->redirect('this');
    }

    public static function wrongTokenAccountMessage(BankWrongTokenAccount $exception): string
    {
        return 'Zadaný API token patří ke špatnému bankovnímu účtu. Zadaný bankovní účet je ' . $exception->getIntendedAccount() . ', token patří k účtu ' . $exception->getTokenAccount() . '.';
    }
}
