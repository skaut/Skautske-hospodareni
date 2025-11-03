<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\Components\DataGrid;
use App\AccountancyModule\Factories\GridFactory;
use Component\Forms\BaseForm;
use Entity\Invoice;
use Manager\InvoiceManager;
use Model\DTO\Google\OAuth;
use Model\DTO\Payment\BankAccount;
use Model\Payment\ReadModel\Queries\BankAccount\BankAccountsAccessibleByUnitsQuery;
use Model\Payment\ReadModel\Queries\OAuthsAccessibleByGroupsQuery;
use Model\Unit\ReadModel\Queries\UnitsDetailQuery;
use Model\Unit\Unit;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;
use Repository\InvoiceRepository;
use Throwable;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Utility\Ares\ViAresParser;

use function array_map;
use function array_unique;
use function assert;
use function dumpe;

class InvoiceListPresenter extends BasePresenter
{
    protected $groupId = null;

    public function __construct(
        private readonly GridFactory $gridFactory,
        protected InvoiceManager $invoiceManager,
        protected InvoiceRepository $invoiceRepository,
    ) {
        parent::__construct();
    }

    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->gridFactory->createSimpleGrid(
            __DIR__ . '/../templates/InvoiceList/grid.latte',
            [],
        );

        $grid->addColumnNumber('name', 'Název')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('dueDate', 'Datum splatnosti')
            ->setSortable();

        $grid->addColumnText('dateOfIssue', 'Datum vystavení')
            ->setSortable();
//        $grid->addColumnText('count','Počet')
//            ->setSortable();
        $grid->addColumnText('issuedBy', 'Vystavil')
            ->setSortable()
            ->setFilterText();

        $grid->addAction('edit', '', 'default', ['id' => 'id'])
            ->setIcon('far fa-edit')
            ->setTitle('Detail')
            ->setClass('btn btn-sm btn-secondary');

        $grid->addAction('delete', '', 'remove!', ['id' => 'id'])
            ->setIcon('far fa-trash-can')
            ->setTitle('Smazat fakturační řadu')
            ->setClass('btn btn-sm btn-danger')
            ->setConfirmation(
                new StringConfirmation('Opravdu chceš smazat řádek %s?', 'name'), // Second parameter is optional
            );

        $grid->addFilterText('search', '', ['year', 'description', 'sequence'])
            ->setPlaceholder('Hledej...');

        $grid->setDataSource($this->invoiceRepository->getGrid());

        return $grid;
    }

    public function handleRemove(int $id): void
    {
        $invoiceSequence = $this->invoiceRepository->find($id);
        try {
            $this->invoiceManager->delete($invoiceSequence);
        } catch (Throwable) {
        }
    }

    protected function createComponentCreateForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addDate('dueDate', 'Datum splatnosti')->addRule(Form::REQUIRED);

        $form->addDate('dateOfIssue', 'Datum vystavení')->addRule(Form::REQUIRED);

        $form->addText('issuedBy', 'Vystavil')->addRule(Form::REQUIRED);

        $customerContainer = $form->addContainer('customer');
        $customerContainer->addText('companyNumber', 'IČO');
        $customerContainer->addSubmit('ares', 'Získat z Aresu')
            ->setValidationScope([$customerContainer->getComponent('companyNumber')])
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary ajax')
            ->onClick[] = function (SubmitButton $button): void {
                $this->getContactInfo($button);
            };

        $customerContainer->addText('vat', 'DIČ');
        $customerContainer->addText('name', 'Název');
        $customerContainer->addText('address', 'Adresa');
        $customerContainer->addCheckbox('vatPayer', 'Plátce DPH');

        $form->addSubmit('send', 'Send');
        $form->onSuccess[] = function (BaseForm $form, ArrayHash $values): void {
            if ($form->isSubmitted() !== $form['send']) {
                return;
            }

            $this->formSucceeded($form, $values);
        };

        return $form;
    }

    public function getContactInfo(SubmitButton $button): void
    {
        $values = $button->getForm()->getComponent('customer')->getValues();

        try {
            $companyInfo = (new ViAresParser())->getAres($values->companyNumber);
        } catch (Throwable $e) {
            dumpe($e);
        }

        $this['createForm']['customer']->setValues($companyInfo->toArray());

        if ($this->isAjax()) {
            $this->redrawControl('createForm');
        } else {
            $this->redirect('this');
        }
    }

    public function formSucceeded(BaseForm $form, ArrayHash $values): void
    {
        $invoice = Invoice::formForm($values);

        $this->invoiceManager->create($invoice);

        $this->flashMessage('Faktura byla vytvořena');
        if ($this->isAjax()) {
            $this['createForm']->setValues([], true);
            $this->redrawControl('grid');
            $this->redrawControl('createForm');
        } else {
            $this->redirect('this');
        }
    }

    /** @return array<int, string> */
    private function bankAccountItems(): array
    {
        $bankAccounts = $this->queryBus->handle(new BankAccountsAccessibleByUnitsQuery($this->groupUnitIds()));

        $items = [];

        foreach ($bankAccounts as $bankAccount) {
            assert($bankAccount instanceof BankAccount);
            $items[$bankAccount->getId()] = $bankAccount->getName();
        }

        return $items;
    }

    /** @return array<string, array<string, string>> */
    private function oAuthItems(): array
    {
        $oAuths = $this->queryBus->handle(new OAuthsAccessibleByGroupsQuery($this->groupUnitIds()));

        $units = $this->queryBus->handle(
            new UnitsDetailQuery(
                array_unique(array_map(
                    function (OAuth $oAuth): int {
                        return $oAuth->getUnitId();
                    },
                    $oAuths,
                )),
            ),
        );

        $items = [];
        foreach ($oAuths as $oAuth) {
            assert($oAuth instanceof OAuth);

            $unit = $units[$oAuth->getUnitId()];
            assert($unit instanceof Unit);

            $items[$unit->getDisplayName()][$oAuth->getId()] = $oAuth->getEmail();
        }

        return $items;
    }

    /** @return int[] */
    private function groupUnitIds(): array
    {
        if ($this->groupId === null) {
            return [$this->unitId->toInt()]; // New group will be created with user's current unit
        }

        $group = $this->model->getGroup($this->groupId);
        assert($group !== null);

        return $group->getUnitIds();
    }
}
