<?php

declare(strict_types=1);

use App\Forms\BaseForm;
use Model\EventEntity;
use Model\ExcelService;
use Model\ExportService;
use Model\MemberService;
use Model\Services\PdfRenderer;
use Model\UnitService;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Skautis\Wsdl\PermissionException;
use Skautis\Wsdl\WsdlException;

trait ParticipantTrait
{
    /**
     * číslo aktuální jendotky
     *
     * @var int
     */
    protected $uid;
    protected $isAllowRepayment;
    protected $isAllowIsAccount;
    protected $isAllowParticipantInsert;
    protected $isAllowParticipantUpdate;
    protected $isAllowParticipantDelete;

    /** @var MemberService */
    protected $memberService;

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

    protected function beforeRender() : void
    {
        parent::beforeRender();
        $this->template->directMemberOnly = $this->getDirectMemberOnly();
    }

    protected function traitDefault(bool $dp, ?string $sort, bool $regNums) : void
    {
        $participants = $this->eventService->getParticipants()->getAll($this->aid);
        try {
            $unitId = $this->uid ?? $this->unitService->getUnitId();
            $list   = $dp ? [] : $this->memberService->getAll($unitId, $this->getDirectMemberOnly(), $participants);
        } catch (WsdlException $e) {
            if (! $dp && strpos('Timeout expired', $e->getMessage())) {
                $this->flashMessage('Bylo vypnuto doplňování osob, protože vypršel časový limit!', 'danger');
                $this->redirect('this', ['aid' => $this->aid, 'uid' => $this->uid, 'dp' => 1]);
            }
            throw $e;
        }
        $this->sortParticipants($participants, $sort);
        $this->template->list         = $list;
        $this->template->participants = $participants;

        $this->template->unit       = $unit = $this->unitService->getDetail($this->uid);
        $this->template->uparrent   = $this->unitService->getDetail($unit->ID_UnitParent);
        $this->template->uchildrens = $this->unitService->getSubunits($unit->ID);
        $this->template->sort       = $sort;
        $this->template->useRegNums = $regNums;
    }

    protected function sortParticipants(&$participants, ?string $sort) : void
    {
        $textItems   = ['regNum', 'isAccount'];
        $numberItems = ['Days', 'payment', 'repayment'];
        if (count($participants) <= 0) {
            return;
        }

        if ($sort === 'regNum') {
            $sort = 'UnitRegistrationNumber';
        } elseif ($sort === null || ! in_array($sort, array_merge($textItems, $numberItems)) || ! property_exists($participants[0], $sort)) {
            $sort = 'Person'; //default sort
        }
        $isNumeric = in_array($sort, $numberItems);
        usort(
            $participants,
            function ($a, $b) use ($sort, $isNumeric) {
                if (! property_exists($a, $sort)) {
                    return true;
                }
                if (! property_exists($b, $sort)) {
                    return false;
                }
                return $isNumeric ? $a->{$sort} > $b->{$sort} : strcasecmp($a->{$sort}, $b->{$sort});
            }
        );
    }

    public function actionExport(int $aid) : void
    {
        $type = $this->eventService->getParticipants()->type; //camp vs general
        try {
            $template = $this->exportService->getParticipants($aid, $this->eventService, $type);
            $this->pdf->render($template, 'seznam-ucastniku.pdf', $type === 'camp');
        } catch (PermissionException $ex) {
            $this->flashMessage('Nemáte oprávnění k záznamu osoby! (' . $ex->getMessage() . ')', 'danger');
            $this->redirect('default', ['aid' => $this->aid]);
        }
        $this->terminate();
    }

    public function actionExportExcel($aid) : void
    {
        $type = $this->eventService->getParticipants()->type; //camp vs general
        try {
            $this->excelService->getParticipants($this->eventService, $this->event, $type);
        } catch (PermissionException $ex) {
            $this->flashMessage('Nemáte oprávnění k záznamu osoby! (' . $ex->getMessage() . ')', 'danger');
            $this->redirect('default', ['aid' => $aid]);
        }
        $this->terminate();
    }

    public function handleRemove($pid) : void
    {
        if (! $this->isAllowParticipantDelete) {
            $this->flashMessage('Nemáte právo mazat účastníky.', 'danger');
            $this->redirect('this');
        }
        $this->eventService->getParticipants()->removeParticipant((int) $pid);
        if ($this->isAjax()) {
            $this->redrawControl('potencialParticipants');
            $this->redrawControl('participants');
        } else {
            $this->redirect('this');
        }
    }

    public function handleAdd(int $pid) : void
    {
        if (! $this->isAllowParticipantInsert) {
            $this->flashMessage('Nemáte oprávnění přidávat účastníky.', 'danger');
            if ($this->isAjax()) {
                $this->sendPayload();
            } else {
                $this->redirect('this');
            }
        }
        $this->eventService->getParticipants()->add($this->aid, $pid);
        if ($this->isAjax()) {
            $this->redrawControl('potencialParticipants');
            $this->redrawControl('participants');
        } else {
            $this->redirect('this');
        }
    }

    /**
     * mění stav jestli vypisovat pouze přímé členy
     */
    public function handleChangeDirectMemberOnly() : void
    {
        $this->setDirectMemberOnly(! $this->getDirectMemberOnly());
        if ($this->isAjax()) {
            $this->redrawControl('potencialParticipants');
        } else {
            $this->redirect('this');
        }
    }

    public function createComponentFormMassList() : Form
    {
        $form           = new BaseForm();
        $form->addSubmit('send')
            ->onClick[] = function (SubmitButton $button) : void {
                $this->formMassListSubmitted($button);
            };

        return $form;
    }

    private function formMassListSubmitted(SubmitButton $button) : void
    {
        if (! $this->isAllowParticipantInsert) {
            $this->flashMessage('Nemáte právo přidávat účastníky.', 'danger');
            $this->redirect('Default:');
        }
        foreach ($button->getForm()->getHttpData(Form::DATA_TEXT, 'massList[]') as $id) {
            $this->eventService->getParticipants()->add($this->aid, $id);
        }
        $this->redirect('this');
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
        $type = $this->eventService->getParticipants()->type; //camp vs general
        if (! $this->isAllowParticipantUpdate) {
            $this->flashMessage('Nemáte právo upravovat účastníky.', 'danger');
            $this->redirect('Default:');
        }
        $values = $button->getForm()->getValues();
        $data   = ['actionId' => $this->aid];
        if ($values['edit']['daysc']) {
            $data['days'] = (int) $values['edit']['days'];
        }
        if ($values['edit']['paymentc']) {
            $data['payment'] = (float) $values['edit']['payment'];
        }
        if ($values['edit']['repaymentc']) {
            $data['repayment'] = (float) $values['edit']['repayment'];
        }
        if ($values['edit']['isAccountc']) {
            $data['isAccount'] = $values['edit']['isAccount'];
        }

        foreach ($button->getForm()->getHttpData(Form::DATA_TEXT, 'massParticipants[]') as $id) {
            $oldData = ($type === 'camp') ? [] : $this->eventService->getParticipants()->get($id);
            $this->eventService->getParticipants()->update((int) $id, array_merge($oldData, $data));
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
            $this->eventService->getParticipants()->removeParticipant((int) $id);
        }
        $this->redirect('this');
    }

    /**
     * formulář na přidání nové osoby
     */
    protected function createComponentFormAddParticipantNew() : Form
    {
        $form = new BaseForm();
        $form->addText('firstName', 'Jméno*')
            ->addRule(Form::FILLED, 'Musíš vyplnit křestní jméno.');
        $form->addText('lastName', 'Příjmení*')
            ->addRule(Form::FILLED, 'Musíš vyplnit příjmení.');
        $form->addText('street', 'Ulice*')
            ->addRule(Form::FILLED, 'Musíš vyplnit ulici.');
        $form->addText('city', 'Město*')
            ->addRule(Form::FILLED, 'Musíš vyplnit město.');
        $form->addText('postcode', 'PSČ*')
            ->addRule(Form::FILLED, 'Musíš vyplnit PSČ.');
        $form->addText('nick', 'Přezdívka');
        $form->addText('birthday', 'Dat. nar.');
        $form->addSubmit('send', 'Založit účastníka')
            ->setAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form) : void {
            $this->formAddParticipantNewSubmitted($form);
        };

        return $form;
    }

    private function formAddParticipantNewSubmitted(Form $form) : void
    {
        if (! $this->isAllowParticipantInsert) {
            $this->flashMessage('Nemáte oprávnění přidávat účastníky.', 'danger');
            if ($this->isAjax()) {
                $this->sendPayload();
            } else {
                $this->redirect('Default:');
            }
        }
        $values = $form->getValues();

        $person = [
            'firstName' => $values['firstName'],
            'lastName' => $values['lastName'],
            'nick' => $values['nick'],
            'Birthday' => date('c', strtotime($values['birthday'])),
            'street' => $values['street'],
            'city' => $values['city'],
            'postcode' => $values['postcode'],
        ];
        $this->eventService->getParticipants()->addNew($this->getCurrentUnitId(), $person);
        $this->redirect('this');
    }

    protected function getDirectMemberOnly() : bool
    {
        return (bool) $this->getSession(__CLASS__)->DirectMemberOnly;
    }

    protected function setDirectMemberOnly($direct)
    {
        return $this->getSession(__CLASS__)->DirectMemberOnly = $direct;
    }
}
