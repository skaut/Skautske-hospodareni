<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule;

use App\Forms\BaseForm;
use Cake\Chronos\ChronosDate;
use Exception;
use Model\DTO\Travel\Contract;
use Model\Services\PdfRenderer;
use Model\Travel\Contract\Passenger;
use Model\TravelService;
use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\UserService;
use Nette\Application\UI\Form;
use Nette\Security\SimpleIdentity;

use function array_column;
use function array_filter;
use function array_key_exists;
use function assert;
use function dirname;

class ContractPresenter extends BasePresenter
{
    public function __construct(private TravelService $travelService, private PdfRenderer $pdf)
    {
        parent::__construct();

        $this->setLayout('layout.new');
    }

    private function isContractAccessible(Contract|null $contract): bool
    {
        $identity = $this->getUser()->getIdentity();

        assert($identity instanceof SimpleIdentity);

        return $contract !== null && array_key_exists($contract->getUnitId(), $identity->access[UserService::ACCESS_READ]);
    }

    private function isContractEditable(Contract|null $contract): bool
    {
        $identity = $this->getUser()->getIdentity();

        assert($identity instanceof SimpleIdentity);

        return $contract !== null && array_key_exists($contract->getUnitId(), $identity->access[UserService::ACCESS_EDIT]);
    }

    public function renderDefault(): void
    {
        $identity = $this->getUser()->getIdentity();
        $unitId   = $this->officialUnit->getId();

        assert($identity instanceof SimpleIdentity);

        if (! array_key_exists($unitId, $identity->access[UserService::ACCESS_READ])) {
            $this->flashMessage('Nemáš přístup ke smlouvám cestovních příkazů.', 'danger');
            $this->redirect('Default:default');
        }

        $this->template->setParameters([
            'list' => $this->travelService->getAllContracts($unitId),
            'canCreate' => array_key_exists($unitId, $identity->access[UserService::ACCESS_EDIT]),
        ]);
    }

    public function actionDetail(int $id): void
    {
        $contract = $this->travelService->getContract($id);

        if (! $this->isContractAccessible($contract)) {
            $this->setView('accessDenied');
            $this->template->setParameters(['message' => 'Nemáte oprávnění ke smlouvě o proplácení cestovních náhrad.']);

            return;
        }

        $commands   = $this->travelService->getAllCommandsByContract($contract->getId());
        $vehicleIds = array_filter(array_column($commands, 'vehicleId'));

        $this->template->setParameters([
            'contract' => $contract,
            'commands' => $commands,
            'vehicles' => $this->travelService->findVehiclesByIds($vehicleIds),
        ]);
    }

    public function actionPrint(int $contractId): void
    {
        $contract = $this->travelService->getContract($contractId);
        if (! $this->isContractAccessible($contract)) {
            $this->setView('accessDenied');
            $this->template->setParameters(['message' => 'Nemáte oprávnění ke smlouvě o proplácení cestovních náhrad.']);

            return;
        }

        switch ($contract->getTemplateVersion()) {
            case 1:
                $templateName = 'ex.contract.old.latte';
                break;
            case 2:
                $templateName = 'ex.contract.noz.latte';
                break;
            default:
                throw new Exception('Neznámá šablona pro ' . $contract->getTemplateVersion());
        }

        $template = $this->template;
        $template->setFile(dirname(__FILE__) . '/../templates/Contract/' . $templateName);
        $template->setParameters([
            'contract' => $contract,
            'unit'     => $this->queryBus->handle(new UnitQuery($contract->getUnitId())),
        ]);

        $this->pdf->render((string) $template, 'Smlouva-o-proplaceni-cestovnich-nahrad.pdf');
    }

    public function handleDelete(int $contractId): void
    {
        $commands = $this->travelService->getAllCommandsByContract($contractId);

        if (! empty($commands)) {
            $this->flashMessage('Nelze smazat smlouvu s navázanými cestovními příkazy!', 'danger');
            $this->redirect('this');
        }

        $contract = $this->travelService->getContract($contractId);
        if (! $this->isContractEditable($contract)) {
            $this->setView('accessDenied');
            $this->template->setParameters(['message' => 'Nemáte oprávnění ke smlouvě o proplácení cestovních náhrad.']);

            return;
        }

        $this->travelService->deleteContract($contractId);
        $this->flashMessage('Smlouva byla smazána', 'success');
        $this->redirect('default');
    }

    protected function createComponentFormCreateContract(): Form
    {
        $form = new BaseForm();

        $form->addText('passengerName', 'Jméno a příjmení řidiče')
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired('Musíte vyplnit jméno řidiče.');
        $form->addText('passengerAddress', 'Bydliště řidiče')
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired('Musíte vyplnit bydliště řidiče.');
        $form->addDate('passengerBirthday', 'Datum narození řidiče')
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired('Musíte vyplnit datum narození řidiče.');
        $form->addText('passengerContact', 'Telefon na řidiče (9cifer)')
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired('Musíte vyplnit telefon na řidiče.')
            ->addRule(Form::NUMERIC, 'Telefon musí být číslo.');

        $form->addText('unitRepresentative', 'Zástupce jednotky')
            ->setRequired('Musíte vyplnit zástupce jednotky')
            ->setHtmlAttribute('class', 'form-control');
        $form->addDate('start', 'Platnost od')
            ->setDefaultValue(ChronosDate::now())
            ->setRequired('Musíte vyplnit od kdy smlouva platí')
            ->setHtmlAttribute('class', 'form-control');

        $form->addSubmit('send', 'Založit smlouvu')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form): void {
            $this->formCreateContractSubmitted($form);
        };

        return $form;
    }

    private function formCreateContractSubmitted(Form $form): void
    {
        $v = $form->getValues();

        $passenger = new Passenger(
            (string) $v->passengerName,
            (string) $v->passengerContact,
            (string) $v->passengerAddress,
            $v->passengerBirthday,
        );

        $this->travelService->createContract($this->getUnitId(), $v->unitRepresentative, new ChronosDate($v->start), $passenger);
        $this->flashMessage('Smlouva byla založena.');

        $this->redirect('default');
    }
}
