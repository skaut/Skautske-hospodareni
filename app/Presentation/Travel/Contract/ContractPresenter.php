<?php

declare(strict_types=1);

namespace App\Presentation\Travel\Contract;

use App\Model\DTO\Travel\Contract;
use App\Model\Services\PdfRenderer;
use App\Model\Travel\Contract\Passenger;
use App\Model\Travel\TravelService;
use App\Model\Unit\ReadModel\Queries\UnitQuery;
use App\Model\User\UserService;
use Cake\Chronos\ChronosDate;
use Component\Forms\BaseForm;
use Exception;
use Nette\Application\UI\Form;
use Nette\Security\SimpleIdentity;

use function array_column;
use function array_filter;
use function array_key_exists;
use function is_array;

class ContractPresenter extends \App\Presentation\Travel\TravelBasePresenter
{
    public function __construct(private TravelService $travelService, private PdfRenderer $pdf)
    {
        parent::__construct();
    }

    private function isContractAccessible(?Contract $contract): bool
    {
        $identity = $this->getUser()->getIdentity();
        if (! $identity instanceof SimpleIdentity) {
            return false;
        }

        return $contract !== null && $this->hasUnitAccess($identity, UserService::ACCESS_READ, $contract->getUnitId());
    }

    private function isContractEditable(?Contract $contract): bool
    {
        $identity = $this->getUser()->getIdentity();
        if (! $identity instanceof SimpleIdentity) {
            return false;
        }

        return $contract !== null && $this->hasUnitAccess($identity, UserService::ACCESS_EDIT, $contract->getUnitId());
    }

    public function renderDefault(): void
    {
        $identity = $this->getUser()->getIdentity();
        $unitId = $this->officialUnit->getId();

        if (! $identity instanceof SimpleIdentity || ! $this->hasUnitAccess($identity, UserService::ACCESS_READ, $unitId)) {
            $this->flashMessage('Nemáš přístup ke smlouvám cestovních příkazů.', 'danger');
            $this->redirect(':Travel:Default:default');
        }

        $this->template->setParameters([
            'list' => $this->travelService->getAllContracts($unitId),
            'canCreate' => $this->hasUnitAccess($identity, UserService::ACCESS_EDIT, $unitId),
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

        $commands = $this->travelService->getAllCommandsByContract($contract->getId());
        $vehicleIds = array_filter(array_column($commands, 'vehicleId'));

        $this->template->setParameters([
            'contract' => $contract,
            'commands' => $commands,
            'vehicles' => $this->travelService->findVehiclesByIds($vehicleIds),
        ]);
    }

    public function actionPrint(int $id): void
    {
        $contract = $this->travelService->getContract($id);
        if (! $this->isContractAccessible($contract)) {
            $this->setView('accessDenied');
            $this->template->setParameters(['message' => 'Nemáte oprávnění ke smlouvě o proplácení cestovních náhrad.']);

            return;
        }

        switch ($contract->getTemplateVersion()) {
            case 1:
                $templateName = __DIR__.'/ex.contract.old.latte';
                break;
            case 2:
                $templateName = __DIR__.'/ex.contract.noz.latte';
                break;
            default:
                throw new Exception('Neznámá šablona pro '.$contract->getTemplateVersion());
        }

        $template = $this->template;
        $template->setFile($templateName);
        $template->setParameters([
            'contract' => $contract,
            'unit' => $this->queryBus->handle(new UnitQuery($contract->getUnitId())),
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
        $v = $form->getValues(\Nette\Utils\ArrayHash::class);

        $passenger = new Passenger(
            (string) $v->passengerName,
            (string) $v->passengerContact,
            (string) $v->passengerAddress,
            $v->passengerBirthday === null ? null : new ChronosDate($v->passengerBirthday),
        );

        $this->travelService->createContract($this->getUnitId(), $v->unitRepresentative, new ChronosDate($v->start), $passenger);
        $this->flashMessage('Smlouva byla založena.');

        $this->redirect('default');
    }

    private function hasUnitAccess(SimpleIdentity $identity, string $accessType, int $unitId): bool
    {
        $access = $identity->getData()['access'] ?? [];
        if (! is_array($access)) {
            return false;
        }

        $units = $access[$accessType] ?? [];
        if (! is_array($units)) {
            return false;
        }

        return array_key_exists($unitId, $units);
    }
}
