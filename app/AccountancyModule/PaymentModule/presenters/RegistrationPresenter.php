<?php

namespace App\AccountancyModule\PaymentModule;

/**
 * @author Hána František <sinacek@gmail.com>
 */
use Nette\Application\UI\Form;

class RegistrationPresenter extends BasePresenter
{

    protected $readUnits;

    protected function startup() : void
    {
        parent::startup();
        $this->template->unitPairs = $this->readUnits = $units = $this->unitService->getReadUnits($this->user);
    }

    public function actionMassAdd($id) : void
    {
        //ověření přístupu
        try {
            $this->template->list = $list = $this->model->getPersonsFromRegistrationWithoutPayment(array_keys($this->readUnits), $id);
        } catch (\InvalidArgumentException $exc) {
            $this->flashMessage("Neoprávněný přístup ke skupině.", "danger");
            $this->redirect("Payment:default");
        }

        $this->template->detail = $detail = $this->model->getGroup(array_keys($this->readUnits), $id);
        $this->template->maxVS = $this->model->getMaxVS($detail->id);

        if (!$detail) {
            $this->flashMessage("Neplatný požadavek na přidání registračních plateb", "danger");
            $this->redirect("Payment:default");
        }


        $form = $this['registrationForm'];
        $form['oid']->setDefaultValue($id);
        foreach ($list as $p) {
            $form->addSelect($p['ID_Person'] . '_email', NULL, $p['emails'])
                ->setPrompt("")
                ->setDefaultValue(key($p['emails']))
                ->setAttribute('class', 'input-xlarge');
        }
    }

    protected function createComponentRegistrationForm($name) : Form
    {
        $form = $this->prepareForm($this, $name);
        $form->addHidden("oid");
        $form->addText("defaultAmount", "Částka:")
            ->setAttribute('class', 'input-mini');
        $form->addDatePicker('defaultMaturity', "Splatnost:")//
        ->setAttribute('class', 'input-small');
        $form->addText("defaultKs", "KS:")
            ->setAttribute('class', 'input-mini');
        $form->addText("defaultNote", "Poznámka:")
            ->setAttribute('class', 'input-small');
        $form->addSubmit('send', 'Přidat vybrané')
            ->setAttribute("class", "btn btn-primary btn-large");

        $form->onSubmit[] = function(Form $form) : void {
            $this->registrationFormSubmitted($form);
        };

        return $form;
    }

    private function registrationFormSubmitted(Form $form) : void
    {
        $values = $form->getValues();
        $checkboxs = $form->getHttpData($form::DATA_TEXT, 'ch[]');
        $vals = $form->getHttpData()['vals'];

        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění pro práci s registrací jednotky", "danger");
            $this->redirect("Payment:detail", ["id" => $values->oid]);
        }

        foreach ($checkboxs as $pid) {
            $pid = substr($pid, 2);
            $tmpAmount = $vals[$pid]['amount'];
            $tmpMaturity = $vals[$pid]['maturity'];
            $tmpKS = $vals[$pid]['ks'];
            $tmpNote = $vals[$pid]['note'];

            $name = $this->noEmpty($vals[$pid]['name']);
            $amount = $tmpAmount == "" ? $this->noEmpty($values['defaultAmount']) : $tmpAmount;
            if ($amount === NULL) {
                $form->addError("Musí být vyplněna částka."); //[$uid . '_' . $p['ID'] . '_amount']
                return;
            }

            if ($tmpMaturity != "") {
                $matArr = preg_split('#[\. ]+#', $tmpMaturity);
                if (count($matArr) == 3) {
                    $maturity = date("Y-m-d", strtotime($matArr[2] . "-" . $matArr[1] . "-" . $matArr[0]));
                } else {
                    $this->flashMessage("Nepodařilo se nastavit splatnost pro $name", "danger");
                    continue;
                }
            } else {
                if ($values['defaultMaturity'] instanceof \DateTime) {
                    $maturity = date("Y-m-d", strtotime($values['defaultMaturity']));
                } else {
                    $form->addError("Musí být vyplněná splatnost."); //[$uid . '_' . $p['ID'] . '_amount']
                    return;
                }
            }
            $email = $this->noEmpty($vals[$pid]['email']);
            $vs = $this->noEmpty($vals[$pid]['vs']);
            $ks = $tmpKS == "" ? $this->noEmpty($values['defaultKs']) : $tmpKS;
            $note = $tmpNote == "" ? $this->noEmpty($values['defaultNote']) : $tmpNote;

            $this->model->createPayment($values->oid, $name, $email, $amount, $maturity, $pid, $vs, $ks, $note);
        }

        $this->flashMessage("Platby byly přidány");
        $this->redirect("Payment:detail", ["id" => $values->oid]);
    }

}
