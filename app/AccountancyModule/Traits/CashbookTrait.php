<?php

use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Model\ExcelService;
use Model\ExportService;
use Model\MemberService;
use Model\Services\PdfRenderer;

trait CashbookTrait
{

    /** @var \Model\EventEntity */
    protected $entityService;

    /** @var PdfRenderer */
    private $pdf;

    /** @var ExportService */
    private $exportService;

    /** @var ExcelService */
    private $excelService;

    /** @var MemberService */
    private $memberService;

    /** @var \Nette\Utils\ArrayHash */
    protected $event;

    public function injectConstruct(
        PdfRenderer $pdf,
        ExportService $exports,
        ExcelService $excel,
        MemberService $members)
    {
        $this->pdf = $pdf;
        $this->exportService = $exports;
        $this->excelService = $excel;
        $this->memberService = $members;
    }

    public function renderEdit($id, $aid) : void
    {
        $this->editableOnly();
        $this->isChitEditable($id, $this->entityService);

        $defaults = $this->entityService->chits->get($id);
        $defaults['id'] = $id;
        $defaults['price'] = $defaults['priceText'];

        if ($defaults['ctype'] == "out") {
            $form = $this['formOutEdit'];
            $form->setDefaults($defaults);
            $this->template->ctype = $defaults['ctype'];
        } else {
            $form = $this['formInEdit'];
            $form->setDefaults($defaults);
        }
        $form['recipient']->setHtmlId("form-edit-recipient");
        $form['price']->setHtmlId("form-edit-price");
        $this->template->form = $form;
        $this->template->autoCompleter = array_values($this->memberService->getCombobox(FALSE, 15));
    }

    public function actionExport($aid) : void
    {
        $template = $this->exportService->getCashbook($this->createTemplate(), $aid, $this->entityService);
        $this->pdf->render($template, 'pokladni-kniha.pdf');
        $this->terminate();
    }

    public function actionExportChitlist($aid) : void
    {
        $template = $this->exportService->getChitlist($this->createTemplate(), $aid, $this->entityService);
        //echo $template;die();
        $this->pdf->render($template, 'seznam-dokladu.pdf');
        $this->terminate();
    }

    public function actionExportExcel($aid) : void
    {
        $this->excelService->getCashbook($this->entityService, $this->event);
        $this->terminate();
    }

    public function actionPrint($id, $aid) : void
    {
        $chits = [$this->entityService->chits->get($id)];
        $template = $this->exportService->getChits($this->createTemplate(), $aid, $this->entityService, $chits);
        $this->pdf->render($template, 'paragony.pdf');
        $this->terminate();
    }

    public function handleRemove($id, $actionId) : void
    {
        $this->editableOnly();
        $this->isChitEditable($id, $this->entityService);

        if ($this->entityService->chits->delete($id, $actionId)) {
            $this->flashMessage("Paragon byl smazán");
        } else {
            $this->flashMessage("Paragon se nepodařilo smazat");
        }

        if ($this->isAjax()) {
            $this->redrawControl("paragony");
            $this->redrawControl("flash");
        } else {
            $this->redirect('this', $actionId);
        }
    }

    protected function createComponentFormMass($name) : Form
    {
        $form = $this->prepareForm($this, $name);
        $form->addSubmit('massPrintSend')
            ->onClick[] = function(SubmitButton $button) : void {
                $this->massPrintSubmitted($button);
            };
        $form->addSubmit('massExportSend')
            ->onClick[] = function(SubmitButton $button) : void {
                $this->massExportSubmitted($button);
            };
        return $form;
    }

    private function massPrintSubmitted(SubmitButton $button) : void
    {
        $chits = $this->entityService->chits->getIn($this->aid, $button->getForm()->getHttpData(Form::DATA_TEXT, 'chits[]'));
        $template = $this->exportService->getChits($this->createTemplate(), $this->aid, $this->entityService, $chits);
        $this->pdf->render($template, 'paragony.pdf');
        $this->terminate();
    }

    private function massExportSubmitted(SubmitButton $button) : void
    {
        $chits = $this->entityService->chits->getIn($this->aid, $button->getForm()->getHttpData(Form::DATA_TEXT, 'chits[]'));
        $this->excelService->getChitsExport($chits);
        $this->terminate();
    }

    //FORM CASHBOOK
    public function getCategoriesByType($form, $dependentSelectBoxName) : array
    {
        return $this->entityService->chits->getCategoriesPairs($form["type"]->getValue(), $this->aid);
    }

    protected function createComponentCashbookForm($name) : Form
    {
        $form = $this->prepareForm($this, $name);
        $form->addDatePicker("date", "Ze dne:")
            ->addRule(Form::FILLED, 'Zadejte datum')
            ->getControlPrototype()->class("form-control input-sm required")->placeholder("Datum");
        $form->addText("num", "Číslo d.:")
            ->setMaxLength(5)
            ->getControlPrototype()->placeholder("Číslo")
            ->class("form-control input-sm");
        $form->addText("purpose", "Účel výplaty:")
            ->setMaxLength(40)
            ->addRule(Form::FILLED, 'Zadejte účel výplaty')
            ->getControlPrototype()->placeholder("Účel")
            ->class("form-control input-sm required");
        $form->addSelect("type", "Typ", ["in" => "Příjmy", "out" => "Výdaje"])
            //->setAttribute("class", "form-control)
            ->setAttribute("size", "2")
            ->setDefaultValue("out")
            ->addRule(Form::FILLED, "Vyberte typ");
        $form->addJSelect("category", "Kategorie", $form["type"], [$this, "getCategoriesByType"])
            ->setAttribute("class", "form-control input-sm");
        $form->addText("recipient", "Vyplaceno komu:")
            ->setMaxLength(64)
            ->setHtmlId("form-recipient")
            ->getControlPrototype()->class("form-control input-sm")->placeholder("Komu/Od");
        $form->addText("price", "Částka: ")
            ->setMaxLength(100)
            ->setHtmlId("form-out-price")
            //                ->addRule(Form::REGEXP, 'Zadejte platnou částku bez mezer', "/^([0-9]+[\+\*])*[0-9]+$/")
            ->getControlPrototype()->placeholder("Částka: 2+3*15")
            ->class("form-control input-sm");
        $form->addHidden("pid");
        $form->addSubmit('send', 'Uložit')
            ->setAttribute("class", "btn btn-primary");
        //$form->setDefaults(array('category' => 'un'));
        $form->onSuccess[] = function(Form $form) : void {
            $this->cashbookFormSubmitted($form);
        };
        return $form;
    }

    /**
     * přidává paragony všech kategorií
     * @param Form $form
     */
    private function cashbookFormSubmitted(Form $form) : void
    {
        if ($form["send"]->isSubmittedBy()) {
            $this->editableOnly();
            $values = $form->getValues();

            try {
                if ($values['pid'] != "") {//EDIT
                    $chitId = $values['pid'];
                    unset($values['id']);
                    $this->isChitEditable($chitId, $this->entityService);
                    if ($this->entityService->chits->update($chitId, $values)) {
                        $this->flashMessage("Paragon byl upraven.");
                    } else {
                        $this->flashMessage("Paragon se nepodařilo upravit.", "danger");
                    }
                } else {//ADD
                    $this->entityService->chits->add($this->aid, $values);
                    $this->flashMessage("Paragon byl úspěšně přidán do seznamu.");
                }
                if ($this->entityService->chits->eventIsInMinus($this->aid)) {
                    $this->flashMessage("Dostali jste se do záporné hodnoty.", "danger");
                }
            } catch (InvalidArgumentException $exc) {
                $this->flashMessage("Paragon se nepodařilo přidat do seznamu.", "danger");
            } catch (\Skautis\Wsdl\WsdlException $se) {
                $this->flashMessage("Nepodařilo se upravit záznamy ve skautisu.", "danger");
            }

            //            if ($this->isAjax()) {
            //                $this->invalidateControl("paragony");
            //                $this->invalidateControl("flash");
            //            } else {
            //                $this->redirect("default", array("aid" => $this->aid));
            //            }
            $this->redirect("default", ["aid" => $this->aid]);
        }
    }

    /**
     * ověřuje editovatelnost paragonu a případně vrací chybovou hlášku rovnou
     * @param type $chitId
     * @param type $service
     */
    protected function isChitEditable($chitId, $service) : ?bool
    {
        $chit = $service->chits->get($chitId);
        if ($chit !== FALSE && is_null($chit->lock)) {
            return TRUE;
        }
        $this->flashMessage("Paragon není možné upravovat!", "danger");
        if ($this->isAjax()) {
            $this->sendPayload();
        } else {
            $this->redirect("this");
        }
    }

    public function render()
    {
        try {
            $this->template->autoCompleter = array_values($this->memberService->getCombobox(FALSE, 15));
        } catch(\Skautis\Wsdl\WsdlException $e) {
            $this->template->autoCompleter = [];
        }
    }

    private function editChit(int $chitId)
    {
        $this->isChitEditable($chitId, $this->entityService);
        $form = $this['cashbookForm'];
        $chit = $this->entityService->chits->get($chitId);

        $form['category']->setItems($this->entityService->chits->getCategoriesPairs($chit->ctype, $this->aid));
        $form->setDefaults([
            "pid" => $chitId,
            "date" => $chit->date->format("j. n. Y"),
            "num" => $chit->num,
            "recipient" => $chit->recipient,
            "purpose" => $chit->purpose,
            "price" => $chit->priceText,
            "type" => $chit->ctype,
            "category" => $chit->category,
        ]);
    }

}
