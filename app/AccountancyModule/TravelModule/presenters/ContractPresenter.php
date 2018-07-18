<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule;

use App\Forms\BaseForm;
use Model\Services\PdfRenderer;
use Model\Travel\Contract\Passenger;
use Model\TravelService;
use Nette\Application\UI\Form;
use function array_column;
use function array_filter;
use function dirname;

class ContractPresenter extends BasePresenter
{
    /** @var TravelService */
    private $travelService;

    /** @var PdfRenderer */
    private $pdf;

    public function __construct(TravelService $travelService, PdfRenderer $pdf)
    {
        parent::__construct();
        $this->travelService = $travelService;
        $this->pdf           = $pdf;
    }

    public function renderDefault() : void
    {
        $this->template->list = $this->travelService->getAllContracts($this->unit->ID);
    }

    public function actionDetail(int $id) : void
    {
        $contract = $this->travelService->getContract($id);

        if ($contract === null || $contract->getUnitId() !== $this->getUnitId()) {
            $this->flashMessage('Nemáte oprávnění k cestovnímu příkazu.', 'danger');
            $this->redirect('default');
        }

        $commands   = $this->travelService->getAllCommandsByContract($contract->getId());
        $vehicleIds = array_filter(array_column($commands, 'vehicleId'));

        $this->template->setParameters(
            [
            'contract' => $contract,
            'commands' => $commands,
            'vehicles' => $this->travelService->findVehiclesByIds($vehicleIds),
            ]
        );
    }

    public function actionPrint($contractId) : void
    {
        $template           = $this->template;
        $template->contract = $contract = $this->travelService->getContract($contractId);
        $template->unit     = $this->unitService->getDetail($contract->getUnitId());

        switch ($contract->getTemplateVersion()) {
            case 1:
                $templateName = 'ex.contract.old.latte';
                break;
            case 2:
                $templateName = 'ex.contract.noz.latte';
                break;
            default:
                throw new \Exception('Neznámá šablona pro ' . $contract->getTemplateVersion());
        }

        $template->setFile(dirname(__FILE__) . '/../templates/Contract/' . $templateName);

        $this->pdf->render((string) $template, 'Smlouva-o-proplaceni-cestovnich-nahrad.pdf');
    }

    public function handleDelete(int $contractId) : void
    {
        $commands = $this->travelService->getAllCommandsByContract($contractId);

        if (! empty($commands)) {
            $this->flashMessage('Nelze smazat smlouvu s navázanými cestovními příkazy!', 'danger');
            $this->redirect('this');
        }

        $this->travelService->deleteContract($contractId);
        $this->flashMessage('Smlouva byla smazána', 'success');
        $this->redirect('default');
    }

    protected function createComponentFormCreateContract() : Form
    {
        $form = new BaseForm();
        $form->addText('passengerName', 'Jméno a příjmení řidiče*')
            ->setAttribute('class', 'form-control')
            ->setRequired('Musíte vyplnit jméno řidiče.');
        $form->addText('passengerAddress', 'Bydliště řidiče*')
            ->setAttribute('class', 'form-control')
            ->setRequired('Musíte vyplnit bydliště řidiče.');
        $form->addDatePicker('passengerBirthday', 'Datum narození řidiče*')
            ->setAttribute('class', 'form-control')
            ->setRequired('Musíte vyplnit datum narození řidiče.');
        $form->addText('passengerContact', 'Telefon na řidiče (9cifer)*')
            ->setAttribute('class', 'form-control')
            ->setRequired('Musíte vyplnit telefon na řidiče.')
            ->addRule(Form::NUMERIC, 'Telefon musí být číslo.');

        $form->addText('unitRepresentative', 'Zástupce jednotky')
            ->setRequired('Musíte vyplnit zástupce jednotky')
            ->setAttribute('class', 'form-control');
        $form->addDatePicker('start', 'Platnost od')
            ->setDefaultValue((new \DateTimeImmutable())->format('Y-m-d'))
            ->setRequired('Musíte vyplnit od kdy smlouva platí')
            ->setAttribute('class', 'form-control');

        $form->addSubmit('send', 'Založit smlouvu')
            ->setAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form) : void {
            $this->formCreateContractSubmitted($form);
        };

        return $form;
    }

    private function formCreateContractSubmitted(Form $form) : void
    {
        $v = $form->getValues();

        $since = \DateTimeImmutable::createFromMutable($v->start);

        $passenger = new Passenger(
            (string) $v->passengerName,
            (string) $v->passengerContact,
            (string) $v->passengerAddress,
            \DateTimeImmutable::createFromMutable($v->passengerBirthday)
        );

        $this->travelService->createContract($this->getUnitId(), $v->unitRepresentative, $since, $passenger);
        $this->flashMessage('Smlouva byla založena.');

        $this->redirect('this');
    }
}
