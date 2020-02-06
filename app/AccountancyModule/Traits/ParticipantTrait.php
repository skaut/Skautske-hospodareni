<?php

declare(strict_types=1);

namespace App\AccountancyModule;

use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Commands\Cashbook\RemoveCampParticipant;
use Model\Cashbook\Commands\Cashbook\RemoveEventParticipant;
use Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use Model\Common\ShouldNotHappen;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;
use Model\EventEntity;
use Model\ExcelService;
use Model\ExportService;
use Model\Participant\Payment\EventType;
use Model\Services\PdfRenderer;
use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\Unit\Unit;
use Model\UnitService;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Skautis\Wsdl\PermissionException;
use function array_merge;
use function assert;
use function count;
use function in_array;
use function property_exists;
use function strcasecmp;
use function usort;

trait ParticipantTrait
{
    /**
     * číslo aktuální jendotky
     *
     * @var int
     */
    protected $uid;
    /** @var bool */
    protected $isAllowRepayment;
    /** @var bool */
    protected $isAllowIsAccount;
    /** @var bool */
    protected $isAllowParticipantUpdate;
    /** @var bool */
    protected $isAllowParticipantDelete;

    /** @var ExportService */
    protected $exportService;

    /** @var ExcelService */
    protected $excelService;

    /** @var EventEntity */
    protected $eventService;

    /** @var UnitService */
    protected $unitService;

    /** @var PdfRenderer */
    protected $pdf;

    /** @var QueryBus */
    protected $queryBus;

    /** @var CommandBus */
    protected $commandBus;

    protected function traitStartup() : void
    {
        parent::startup();
        if ($this->aid === null) {
            $this->flashMessage('Nepovolený přístup', 'danger');
            $this->redirect('Default:');
        }

        $uid       = $this->getParameter('uid', null);
        $this->uid = $uid !== null ? (int) $uid : null;
    }

    protected function traitDefault(?string $sort, bool $regNums) : void
    {
        if ($this->type === EventType::GENERAL) {
            $participants = $this->queryBus->handle(new EventParticipantListQuery(new SkautisEventId($this->aid)));
        } elseif ($this->type === EventType::CAMP) {
            $participants = $this->queryBus->handle(new CampParticipantListQuery(new SkautisCampId($this->aid)));
        } else {
            throw new ShouldNotHappen('Participants have just general event or camp!');
        }

        $this->sortParticipants($participants, $sort);
        $unit = $this->queryBus->handle(new UnitQuery($this->uid ?? $this->unitService->getUnitId()));
        assert($unit instanceof Unit);

        $sortOptions = [
            'displayName' => 'Jméno',
            'unitRegistrationNumber' => 'Jednotka',
            'onAccount' => 'Na účet?',
            'days' => 'Dnů',
            'payment' => 'Částka',
            'repayment' => 'Vratka',
            'birthday' => 'Věk',
        ];
        if (! $this->isAllowRepayment) {
            unset($sortOptions['repayment']);
        }
        if (! $this->isAllowIsAccount) {
            unset($sortOptions['onAccount']);
        }

        $this->template->setParameters([
            'participants' => $participants,
            'unit'       => $unit,
            'sort'       => $sort ?? 'displayName',
            'sortOptions' => $sortOptions,
            'useRegNums' => $regNums,
        ]);
    }

    /**
     * @param mixed[] $participants
     */
    protected function sortParticipants(array &$participants, ?string $sort) : void
    {
        $textItems   = ['unitRegistrationNumber', 'onAccount'];
        $numberItems = ['days', 'payment', 'repayment', 'birthday'];
        if (count($participants) <= 0) {
            return;
        }

        if ($sort === null || ! in_array($sort, array_merge($textItems, $numberItems)) || ! (property_exists($participants[0], $sort) || isset($participants[0]->{$sort}))) {
            $sort = 'displayName'; //default sort
        }
        $isNumeric = in_array($sort, $numberItems);
        usort(
            $participants,
            function ($a, $b) use ($sort, $isNumeric) {
                if (! (property_exists($a, $sort) || isset($a->{$sort}))) {
                    return true;
                }
                if (! (property_exists($b, $sort) || isset($b->{$sort}))) {
                    return false;
                }

                return $isNumeric ? $a->{$sort} > $b->{$sort} : strcasecmp($a->{$sort} ?? '', $b->{$sort} ?? '');
            }
        );
    }

    public function actionExport(int $aid) : void
    {
        $type = $this->eventService->getParticipants()->type; //camp vs general
        try {
            $template = $this->exportService->getParticipants($aid, $type);
            $this->pdf->render($template, 'seznam-ucastniku.pdf', $type === 'camp');
        } catch (PermissionException $ex) {
            $this->flashMessage('Nemáte oprávnění k záznamu osoby! (' . $ex->getMessage() . ')', 'danger');
            $this->redirect('default', ['aid' => $this->aid]);
        }
        $this->terminate();
    }

    public function handleRemove(int $pid) : void
    {
        if (! $this->isAllowParticipantDelete) {
            $this->flashMessage('Nemáte právo mazat účastníky.', 'danger');
            $this->redirect('this');
        }
        $this->commandBus->handle(
            $this->type === 'camp'
                ? new RemoveCampParticipant($pid)
                : new RemoveEventParticipant($pid)
        );
        if ($this->isAjax()) {
            $this->redrawControl('potencialParticipants');
            $this->redrawControl('participants');
        } else {
            $this->redirect('this');
        }
    }

    public function createComponentFormMassParticipants() : BaseForm
    {
        $form = new BaseForm();

        $editCon = $form->addContainer('edit');
        $editCon->addText('days', 'Dní');
        $editCon->addText('payment', 'Částka');
        $editCon->addText('repayment', 'Vratka');
        $editCon->addRadioList('isAccount', 'Na účet?', ['N' => 'Ne', 'Y' => 'Ano']);
        $editCon->addCheckbox('daysc');
        $editCon->addCheckbox('paymentc');
        $editCon->addCheckbox('repaymentc');
        $editCon->addCheckbox('isAccountc'); //->setDefaultValue(TRUE);
        $editCon->addSubmit('send', 'Upravit')
            ->setAttribute('class', 'btn btn-info btn-small')
            ->onClick[] = [$this, 'massEditSubmitted'];

        $form->addSubmit('send', 'Odebrat vybrané')
            ->onClick[] = [$this, 'massRemoveSubmitted'];

        return $form;
    }

    public function massEditSubmitted(SubmitButton $button) : void
    {
        if (! $this->isAllowParticipantUpdate) {
            $this->flashMessage('Nemáte právo upravovat účastníky.', 'danger');
            $this->redirect('Default:');
        }
        $values = $button->getForm()->getValues();

        foreach ($button->getForm()->getHttpData(Form::DATA_TEXT, 'massParticipants[]') as $id) {
            if ($values['edit']['daysc']) {
                $this->eventService->getParticipants()->update((int) $id, $this->aid, ['days' => (int) $values['edit']['days']]);
            }
            if ($values['edit']['paymentc']) {
                $this->eventService->getParticipants()->update((int) $id, $this->aid, ['payment' => (float) $values['edit']['payment']]);
            }
            if ($values['edit']['repaymentc']) {
                $this->eventService->getParticipants()->update((int) $id, $this->aid, ['payment' => (float) $values['edit']['repayment']]);
            }
            if (! $values['edit']['isAccountc']) {
                continue;
            }

            $this->eventService->getParticipants()->update((int) $id, $this->aid, ['isAccount' => $values['edit']['isAccount']]);
        }
        $this->redirect('this');
    }

    public function massRemoveSubmitted(SubmitButton $button) : void
    {
        if (! $this->isAllowParticipantDelete) {
            $this->flashMessage('Nemáte právo mazat účastníky.', 'danger');
            $this->redirect('Default:');
        }

        foreach ($button->getForm()->getHttpData(Form::DATA_TEXT, 'massParticipants[]') as $id) {
            $this->commandBus->handle(
                $this->type === 'camp'
                    ? new RemoveCampParticipant((int) $id)
                    : new RemoveEventParticipant((int) $id)
            );
        }
        $this->redirect('this');
    }
}
