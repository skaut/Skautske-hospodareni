<?php

namespace App;

use App\Forms\BaseForm;
use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class OfflinePresenter extends BasePresenter
{

    public function actionSync() : void
    {
        if (!$this->user->isLoggedIn()) {
            $this->redirect(":Default:", ["backlink" => $this->storeRequest('+ 3 days')]);
        }
        $this->template->list = $this->context->eventService->event->getAll(date("Y"), "draft");
    }

    public function handleSynchronize($aid) : void
    {
        $this->redrawControl();
    }

    public function renderList() : void
    {

    }

    public function actionManifest() : void
    {
        $this->context->httpResponse->setContentType('Context-Type:', 'text/cache-manifest');

        @$cssFile = reset($this['css']->getCompiler()->generate());
        $this->template->css = "webtemp/" . $cssFile->file . "?" . $cssFile->lastModified; //name
        @$jsFile = reset($this['js']->getCompiler()->generate());
        $this->template->js = "webtemp/" . $jsFile->file . "?" . $jsFile->lastModified; //name
    }

    public function actionOut() : void
    {
        $this['formOut']['category']->setItems($this->context->getService("eventService")->chits->getCategoriesPairs('out'));
        $this->template->setFile(dirname(__FILE__) . '/../templates/Offline/form.latte');
        $this->template->form = $this['formOut'];
    }

    public function actionIn() : void
    {
        $this['formIn']['category']->setItems($this->context->getService("eventService")->chits->getCategoriesPairs('in'));
        $this->template->setFile(dirname(__FILE__) . '/../templates/Offline/form.latte');
        $this->template->form = $this['formIn'];
    }

    /**
     * generuje základní Form pro ostatní formuláře
     * @param Presenter $thisP
     * @param string $name
     * @return Form
     */
    protected function createComponentFormOut() : BaseForm
    {
        $form = new BaseForm();
        $form->getElementPrototype()->class[] = "offline";
        $form->addDatePicker("date", "Ze dne:", 15)
            ->addRule(Form::FILLED, 'Zadejte datum')
            ->getControlPrototype()->class("input-medium");
        //@TODO kontrola platneho data, problem s componentou
        $form->addText("recipient", "Vyplaceno komu:")
            ->setMaxLength(50)
            ->setHtmlId("form-out-recipient")
            ->getControlPrototype()->class("input-medium");
        $form->addText("purpose", "Účel výplaty:")
            ->setMaxLength(40)
            ->addRule(Form::FILLED, 'Zadejte účel výplaty')
            ->getControlPrototype()->placeholder("3 první položky")
            ->class("input-medium");
        $form->addText("price", "Částka: ")
            ->setMaxLength(100)
            ->setHtmlId("form-out-price")
            ->getControlPrototype()->placeholder("např. 20+15*3")
            ->class("input-medium");

        $form->addRadioList("category", "Typ: ")
            ->addRule(Form::FILLED, 'Zadej typ paragonu');
        $form->addHidden("type", "out");
        $form->addSubmit('send', 'Uložit')
            ->setAttribute("class", "btn btn-primary");

        return $form;
    }

    protected function createComponentFormIn() : BaseForm
    {
        $form = new BaseForm();
        $form->getElementPrototype()->class[] = "offline";
        $form->addDatePicker("date", "Ze dne:", 15)
            ->addRule(Form::FILLED, 'Zadejte datum')
            ->getControlPrototype()->class("input-medium");
        $form->addText("recipient", "Přijato od:")
            ->setMaxLength(30)
            ->setHtmlId("form-in-recipient")
            ->getControlPrototype()->class("input-medium");
        $form->addText("purpose", "Účel příjmu:")
            ->setMaxLength(40)
            ->addRule(Form::FILLED, 'Zadejte účel přijmu')
            ->getControlPrototype()->class("input-medium");
        $form->addText("price", "Částka: ")
            ->setMaxLength(100)
            ->setHtmlId("form-in-price")
            //->addRule(Form::REGEXP, 'Zadejte platnou částku', "/^([0-9]+(.[0-9]{0,2})?[\+\*])*[0-9]+([.][0-9]{0,2})?$/")
            ->getControlPrototype()->placeholder("např. 20+15*3")
            ->class("input-medium");
        $form->addRadioList("category", "Typ: ")
            ->addRule(Form::FILLED, 'Zadej typ paragonu');
        $form->addHidden("type", "in");
        $form->addSubmit('send', 'Uložit')
            ->getControlPrototype()->setAttribute("class", "btn btn-primary");

        return $form;
    }

}
