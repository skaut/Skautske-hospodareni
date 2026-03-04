<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\Components\DataGrid;
use App\AccountancyModule\Factories\GridFactory;
use Component\Forms\BaseForm;
use Entity\BankAccount;
use Entity\GoogleOAuth;
use Entity\InvoiceSequence;
use Illuminate\Support\Collection;
use Manager\InvoiceSequenceManager;
use Model\DTO\Google\OAuth;
use Model\Payment\ReadModel\Queries\OAuthsAccessibleByGroupsQuery;
use Model\Unit\ReadModel\Queries\UnitsDetailQuery;
use Model\Unit\Unit;
use Model\UnitService;
use Nette\Forms\Form;
use Repository\BankAccountRepository;
use Repository\InvoiceSequenceRepository;
use Repository\RepositoryService;
use Throwable;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

use function array_map;
use function array_unique;
use function assert;

class InvoiceSequenceListPresenter extends BasePresenter
{
    protected ?int $groupId = null;

    public function __construct(
        private readonly GridFactory $gridFactory,
        protected InvoiceSequenceManager $invoiceSequenceManager,
        protected InvoiceSequenceRepository $invoiceSequenceRepository,
        protected UnitService $unitRepository,
        protected BankAccountRepository $bankAccountRepository,
        protected RepositoryService $repositoryService,
    ) {
        parent::__construct();
    }

    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->gridFactory->createSimpleGrid(
            __DIR__.'/../templates/InvoiceSequenceList/grid.latte',
            [],
        );

        $grid->addColumnNumber('year', 'Rok')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('unit', 'Jednotka')
            ->setSortable();

        $grid->addColumnText('description', 'Popis')
            ->setSortable();
        //        $grid->addColumnText('count','Počet')
        //            ->setSortable();
        $grid->addColumnText('sequence', 'Řada')
            ->setSortable()
            ->setFilterText();

        $grid->addAction('edit', '', 'InvoiceList:default', ['invoiceSequenceId' => 'id'])
            ->setIcon('far fa-edit')
            ->setTitle('Detail')
            ->setClass('btn btn-sm btn-secondary');

        $grid->addAction('delete', '', 'remove!', ['id' => 'id'])
            ->setIcon('far fa-trash-can')
            ->setTitle('Smazat fakturační řadu')
            ->setClass('btn btn-sm btn-danger')
            ->setConfirmation(
                new StringConfirmation('Opravdu chceš smazat řádek %s?', 'sequence'), // Second parameter is optional
            );

        $grid->addFilterText('search', '', ['year', 'description', 'sequence'])
            ->setPlaceholder('Hledej...');

        $grid->setDataSource($this->invoiceSequenceRepository->getGrid());

        return $grid;
    }

    public function handleRemove(int $id): void
    {
        $invoiceSequence = $this->invoiceSequenceRepository->find($id);
        try {
            $this->invoiceSequenceManager->delete($invoiceSequence);
        } catch (Throwable) {
        }
    }

    protected function createComponentCreateForm(): BaseForm
    {
        $unit = $this->unitRepository->getOfficialUnit();

        $form = new BaseForm();
        $supplier = $form->addContainer('supplier');
        $supplier->addText('name', 'Jméno')
            ->setHtmlAttribute('title', 'Data jsou načteny ze skautiIsu')
            ->setDisabled()
            ->setDefaultValue($unit->getDisplayName());
        $supplier->addText('street', 'Ulice')
            ->setHtmlAttribute('title', 'Data jsou načteny ze skautiIsu')
            ->setDisabled()
            ->setDefaultValue($unit->getStreet());
        $supplier->addText('city', 'Město')
            ->setHtmlAttribute('title', 'Data jsou načteny ze skautiIsu')
            ->setDisabled()
            ->setDefaultValue($unit->getCity());
        $supplier->addText('zip', 'PSČ')
            ->setHtmlAttribute('title', 'Data jsou načteny ze skautiIsu')
            ->setDisabled()
            ->setDefaultValue($unit->getPostcode());
        $supplier->addText('companyNumber', 'Ičo')
            ->setHtmlAttribute('title', 'Data jsou načteny ze skautiIsu')
            ->setDisabled()
        ->setDefaultValue($unit->getIc());
        $vatControl = $supplier->addCheckbox('isVatPayer', 'Jednotka je plátce DPH');
        $vatControl
            ->addCondition(Form::EQUAL, true)
            ->toggle('frm-createForm-supplier-vatNumber');
        $supplier->addText('vatNumber', 'DIČ')
            ->addConditionOn($vatControl, Form::EQUAL, true)
            ->setRequired('Plátce DPH musí mít vyplněné DIČ');
        $supplier->addText('phone', 'Kontaktní telefon');

        $form->addText('sequence', 'Prefix')
            ->addRule(Form::MAX_LENGTH, 'Maximální delka prefixu je 5 znaků', 5)
            ->addRule(Form::MIN_LENGTH, 'Minimální delka prefixu je 1 znak', 1)
            ->addRule(Form::REQUIRED, 'Prefix musí být vyplněný');
        $form->addYearSelect('year', 'Rok')->setDefaultValue('now');
        $form->addText('description', 'Popis');
        $form->addSelect('bankAccount', 'Bankovní účet', $this->bankAccountItems())
            ->setRequired(false)
            ->setPrompt('Vyberte bankovní účet');

        $form->addSelect('oAuthId', 'E-mail odesílatele', $this->oAuthItems())
            ->setPrompt('Vyberte e-mail')
            ->setHtmlAttribute('class', 'ui--emailSelectbox'); // For acceptance testing

        $form->addInteger('defaultDueDate', 'Výchozí datum splatnosti')->setDefaultValue(14);

        $form->addSubmit('send', 'Send');
        $form->onSuccess[] = function (BaseForm $form): void {
            $this->formSucceeded($form);
        };

        return $form;
    }

    public function formSucceeded(BaseForm $form): void
    {
        $values = $form->getValues();

        $unit = $this->getCurrentUnitId();
        $account = $this->repositoryService->castToEntity(BankAccount::class)($values->bankAccount, new Collection()) ?? null;
        $googleOAuth = $this->repositoryService->castToEntity(GoogleOAuth::class)($values->oAuthId, new Collection()) ?? null;
        $invoiceSequence = InvoiceSequence::fromForm($unit, $values, $account, $googleOAuth);
        $invoiceSequence->setSequenceId($this->invoiceSequenceRepository->getNextSequenceId($this->unitId, $values->year));

        $this->invoiceSequenceManager->create($invoiceSequence);

        $this->flashMessage('Invoice sequence created');
        if ($this->isAjax()) {
            $this->redrawControl('grid');
        } else {
            $this->redirect('this');
        }
    }

    /** @return array<int, string> */
    private function bankAccountItems(): array
    {
        $bankAccounts = $this->bankAccountRepository->findByUnitId($this->unitId->toInt());
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
        return [$this->unitId->toInt()];
    }
}
