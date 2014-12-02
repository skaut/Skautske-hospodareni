<?php

namespace App\AccountancyModule\PaymentModule;

/**
 * @author Hána František <sinacek@gmail.com>
 */
use Nette\Application\UI\Form;

class RegistrationPresenter extends BasePresenter {

    public function actionMassAdd($id) {
        //ověření přístupu
        $this->template->list = $data = $this->model->getRegistrationPersons($this->aid, $id);
        $this->template->detail = $detail = $this->model->getGroup($this->aid, $id);

        if (!$detail) {
            $this->flashMessage("Neplatný požadavek na přidání registračních plateb", "error");
            $this->redirect("Payment:detail", array("id" => $id));
        }

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
                        ->setDefaultValue($detail['maturity'] instanceof \DateTime ? $detail['maturity']->format("j.n.Y") : "")
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
        $values = $form->getValues();
        if(!$this->isEditable){
            $this->flashMessage("Nemáte oprávnění pro práci s registrací jednotky", "error");
            $this->redirect("Payment:detail", array("id" => $values->oid));
        }
        $data = $this->model->getRegistrationPersons($this->aid, $values->oid);
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

                $this->model->createPayment($values->oid, $name, $email, $amount, $maturity, $p['ID_Person'], $vs, $ks, $note);
            }
        }
        $this->flashMessage("Platby byly přidány");
        $this->redirect("Payment:detail", array("id" => $values->oid));
    }
}
