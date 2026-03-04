<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\Components\DataGrid;
use App\AccountancyModule\Factories\GridFactory;
use App\AccountancyModule\PaymentModule\Factories\IInvoiceFormFactory;
use Entity\Invoice;
use Entity\InvoiceSequence;
use Http\Discovery\Exception\NotFoundException;
use Manager\InvoiceManager;
use Model\ExportService;
use Model\Services\PdfRenderer;
use Nette\Utils\ArrayHash;
use Repository\InvoiceRepository;
use Repository\InvoiceSequenceRepository;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;

class InvoiceListPresenter extends BasePresenter
{
    protected ?int $groupId = null;
    protected InvoiceSequence $invoiceSequence;

    public function __construct(
        private readonly GridFactory $gridFactory,
        protected InvoiceManager $invoiceManager,
        protected InvoiceRepository $invoiceRepository,
        protected InvoiceSequenceRepository $invoiceSequenceRepository,
        protected IInvoiceFormFactory $invoiceFormFactory,
        protected ExportService $exportService,
        protected PdfRenderer $pdf,
    ) {
        parent::__construct();
    }

    public function actionDefault(int $invoiceSequenceId): void
    {
        $invoiceSequence = $this->invoiceSequenceRepository->find($invoiceSequenceId);
        if ($invoiceSequence === null) {
            $this->flashMessage('Fakturační řada nebyla nalezena', 'danger');
            $this->redirect('InvoiceSequenceList:default');
        }
        $this->invoiceSequence = $invoiceSequence;
        // dump($this->invoiceSequence->getInvoices()->first()->getInvoiceNumber());
    }

    public function renderEdit(int $id): void
    {
        $this->template->setParameters(['invoice' => $this->invoiceRepository->find($id)]);
    }

    public function renderDetail(int $id): void
    {
        /** @var Invoice $invoice */
        $invoice = $this->invoiceRepository->find($id);

        $data = [
            // Data dodavatele (proměnná {$supplier->...})
            'supplier' => [
                'name' => $invoice->getSupplier()->getName(),
                'street' => $invoice->getSupplier()->getAddress()->getStreet(),
                'city' => $invoice->getSupplier()->getAddress()->getCity(),
                'zip' => $invoice->getSupplier()->getAddress()->getZipCode(),
                'country' => 'Česká republika',
                'ic' => $invoice->getSupplier()->getCompanyNumber(),

                'mobil' => $invoice->getSequence()->getPhone(),
                'email' => $invoice->getSequence()->getOauth()->getEmail(),

                'bankName' => $invoice->getBankName(),
                'bankAccount' => $invoice->getAccountNumber()->getNumberWithPrefixAndBankCode(),
                'iban' => $invoice->getIban(),
                'bic' => $invoice->getBic(),

                'vatStatusText' => $invoice->getSupplier()->isVatPayer() ? 'Plátce DPH' : 'Neplátce DPH',
                'vat' => $invoice->getSupplier()->getVatNumber(),
            ],

            // Data odběratele (proměnná {$customer->...})
            'customer' => [
                'name' => $invoice->getCustomer()->getName(),
                'address' => $invoice->getCustomer()->getAddress()->getFullAddress(),
                'ic' => $invoice->getCustomer()->getCompanyNumber(),
                'dic' => $invoice->getCustomer()->getVatNumber(),
            ],

            // Data faktury (proměnná {$invoice->...})
            'invoice' => [
                'number' => $invoice->getInvoiceNumber(),
                'variableSymbol' => $invoice->getVariableSymbol(),
                'constantSymbol' => '0008',
                'specificSymbol' => '',
                'paymentMethod' => $invoice->getPaymentType(),

                'dateIssued' => $invoice->getDateOfIssue(),
                'dateDue' => $invoice->getDueDate(),

                'items' => $invoice->getItems(),

                // Celkové částky
                'totalAmount' => $invoice->getTotalAmount(),
                'deposits' => 0.00, // [cite: 38]
                'amountDue' => $invoice->getTotalAmount(),
            ],
            'user' => [
                'name' => $invoice->getIssuedBy(),
            ],
        ];

        $param = ArrayHash::from($data);

        $this->template->setParameters((array) $param);
    }

    protected function createComponentCreateForm(): Components\InvoiceForm
    {
        return $this->invoiceFormFactory->create($this->invoiceSequence);
    }

    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->gridFactory->createSimpleGrid(
            __DIR__.'/../templates/InvoiceList/grid.latte',
            [],
        );

        $grid->addColumnNumber('id', 'Id')
             ->setSortable()
             ->setFilterText();
        $grid->addColumnNumber('invoiceId', 'Id Faktury')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('variable_symbol', 'VS')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('name', 'Název', 'customer.name')
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

        $grid->addAction('edit', '', 'edit', ['id' => 'id'])
            ->setIcon('far fa-edit')
            ->setTitle('Detail')
            ->setClass('btn btn-sm btn-secondary');
        $grid->addAction('downloadPdf', '', 'downloadPdf!', ['id' => 'id'])
            ->setIcon('far fa-edit')
            ->setTitle('Detail')
            ->setClass('btn btn-sm btn-secondary');

        $grid->addAction('delete', '', 'remove!', ['id' => 'id'])
            ->setIcon('far fa-trash-can')
            ->setTitle('Smazat fakturační řadu')
            ->setClass('btn btn-sm btn-danger')
            ->setConfirmation(
                new StringConfirmation('Opravdu chceš smazat řádek %s?', 'customer.name'), // Second parameter is optional
            );

        $grid->addFilterText('search', '', ['year', 'description', 'sequence'])
            ->setPlaceholder('Hledej...');

        $grid->setDataSource($this->invoiceRepository->getGrid($this->invoiceSequence));

        return $grid;
    }

    public function handleDownloadPdf(int $id): void
    {
        /*if (! $this->authorizator->isAllowed(Camp::ACCESS_FUNCTIONS, $aid)) {
            $this->flashMessage('Nemáte právo přistupovat k táboru', 'warning');
            $this->redirect('default', ['aid' => $aid]);
        }*/

        /** @var Invoice $invoice */
        $invoice = $this->invoiceRepository->find($id);

        try {
            $template = $this->exportService->getInvoice($invoice);
            $this->pdf->render($template, $invoice->getInvoiceNumber().'.pdf');
            $this->terminate();
        } catch (NotFoundException $e) {
        }
    }

    public function handleRemove(int $id): void
    {
        /*$invoiceSequence = $this->invoiceRepository->find($id);
        try {
            $this->invoiceManager->delete($invoiceSequence);
        } catch (Throwable) {
        }*/
    }
}
