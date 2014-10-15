<?php

namespace App\AccountancyModule\PaymentModule;

/**
 * @author Hána František <sinacek@gmail.com>
 */
use Nette\Application\UI\Form;

class RegistrationPresenter extends BasePresenter {

    protected $model;

    public function __construct(\Model\PaymentService $paymentService) {
        parent::__construct();
        $this->model = $paymentService;
    }

//    public function renderDefault() {
//        $this->template->detail = $this->context->unitService->getDetail();
//        $this->template->list = $this->model->getRegistrations();
//    }

//    public function handleCreateGroup($regId) {
//        $reg = $this->model->getRegistration($regId);
//        $groupId = $this->model->createGroup('registration', $reg->ID, "Registrace " . $reg->Year . " (" . $reg->RegistrationNumber . ")", $this->model->getLocalId($reg->ID_Unit, "unit"), ($reg->Year + 1) . "-01-15");
//        $this->redirect("Payment:detail", array("id" => $groupId));
//    }

    public function actionMassAdd($id) {
        $this->template->list = $data = $this->model->getRegistrationPersons($id);
        $this->template->detail = $detail = $this->model->getGroup($id);

        $form = $this['registrationForm'];
        $form['oid']->setDefaultValue($id);
        foreach ($data as $u) {
            foreach ($u as $p) {
                $form->addCheckbox($p['ID_Unit'] . '_' . $p['ID_Person'] . '_' . 'check');
                $form->addText($p['ID_Unit'] . '_' . $p['ID_Person'] . '_name')
                        ->setDefaultValue($p['Person']);
                $form->addSelect($p['ID_Unit'] . '_' . $p['ID_Person'] . '_email', NULL, array("") + $p['emails'])
                        ->setDefaultValue(key($p['emails']))
                        ->setAttribute('class', 'input-xlarge');
                $form->addText($p['ID_Unit'] . '_' . $p['ID_Person'] . '_' . 'amount')
                        ->addRule(Form::FLOAT, 'Částka musí být číslo')
                        ->setDefaultValue((int) $p['AmountTotal'])
                        ->setAttribute('class', 'input-mini');
                $form->addDatePicker($p['ID_Unit'] . '_' . $p['ID_Person'] . '_' . 'maturity')
                        ->setDefaultValue($detail['maturity']->format("j.n.Y"))
                        ->setAttribute('class', 'input-small');
                $form->addText($p['ID_Unit'] . '_' . $p['ID_Person'] . '_' . 'vs')
                        ->setAttribute('class', 'input-small');
                $form->addText($p['ID_Unit'] . '_' . $p['ID_Person'] . '_' . 'ks')
                        ->setDefaultValue($detail['ks'])
                        ->setAttribute('class', 'input-mini');
                $form->addText($p['ID_Unit'] . '_' . $p['ID_Person'] . '_' . 'note')
                        ->setAttribute('class', 'input-small');
            }
        }
    }

    public function createComponentRegistrationForm($name) {
        $form = new Form($this, $name);

        $form->addHidden("oid");
        $form->addSubmit('send', 'Přidat vybrané')
                ->setAttribute("class", "btn btn-primary btn-large");
        $form->onSubmit[] = array($this, $name . 'Submitted');
        return $form;
    }

    function registrationFormSubmitted(Form $form) {
//        if (!$this->isAllowed("EV_EventGeneral_UPDATE")) {
//            $this->flashMessage("Nemáte oprávnění pro úpravu akce", "danger");
//            $this->redirect("this");
//        }
        $values = $form->getValues();
        $data = $this->model->getRegistrationPersons($values->oid);
        foreach ($data as $u) {
            foreach ($u as $p) {
                if (!$values->{$p['ID_Unit'] . '_' . $p['ID_Person'] . '_' . 'check'}) {
                    continue;
                }

                $name = $this->noEmpty($values->{$p['ID_Unit'] . '_' . $p['ID_Person'] . '_' . 'name'});
                $amount = $this->noEmpty($values->{$p['ID_Unit'] . '_' . $p['ID_Person'] . '_' . 'amount'});
                $maturity = date("Y-m-d", strtotime($values->{$p['ID_Unit'] . '_' . $p['ID_Person'] . '_' . 'maturity'}));
                $email = $this->noEmpty($values->{$p['ID_Unit'] . '_' . $p['ID_Person'] . '_' . 'email'});
                $vs = $this->noEmpty($values->{$p['ID_Unit'] . '_' . $p['ID_Person'] . '_' . 'vs'});
                $ks = $this->noEmpty($values->{$p['ID_Unit'] . '_' . $p['ID_Person'] . '_' . 'ks'});
                $note = $this->noEmpty($values->{$p['ID_Unit'] . '_' . $p['ID_Person'] . '_' . 'note'});

                $this->model->createPayment($values->oid, $name, $email, $amount, $p['ID_Person'], $maturity, $vs, $ks, $note);
            }
        }
        $this->flashMessage("Platby byly přidány");
        $this->redirect("Payment:detail", array("id" => $values->oid));
    }

    protected function noEmpty($v) {
        return $v == "" ? NULL : $v;
    }

}
