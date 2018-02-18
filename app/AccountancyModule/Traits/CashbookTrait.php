<?php

use App\AccountancyModule\Components\CashbookControl;
use App\AccountancyModule\Factories\ICashbookControlFactory;
use App\Forms\BaseForm;
use Cake\Chronos\Date;
use eGen\MessageBus\Bus\CommandBus;
use Model\Cashbook\CashbookNotFoundException;
use Model\Cashbook\ChitLockedException;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\ChitNumber;
use Model\Cashbook\Cashbook\Recipient;
use Model\Cashbook\Commands\Cashbook\AddChitToCashbook;
use Model\Cashbook\Commands\Cashbook\UpdateChit;
use Model\MemberService;
use Model\Services\PdfRenderer;
use Nette\Forms\IControl;

trait CashbookTrait
{

    /** @var \Model\EventEntity */
    protected $entityService;

    /** @var PdfRenderer */
    private $pdf;

    /** @var MemberService */
    private $memberService;

    /** @var \Nette\Utils\ArrayHash */
    protected $event;

    /** @var CommandBus */
    protected $commandBus;

    /** @var \Model\Auth\IAuthorizator */
    protected $authorizator;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var ICashbookControlFactory */
    private $cashbookFactory;

    public function injectConstruct(PdfRenderer $pdf, MemberService $members, ICashbookControlFactory $cashbookFactory): void
    {
        $this->pdf = $pdf;
        $this->memberService = $members;
        $this->cashbookFactory = $cashbookFactory;
    }

    //FORM CASHBOOK
    public function getCategoriesByType(BaseForm $form): array
    {
        return $this->entityService->chits->getCategoriesPairs($form["type"]->getValue(), $this->aid);
    }

    protected function createComponentCashbookForm(string $name): BaseForm
    {
        $form = new BaseForm();
        $this->addComponent($form, $name); // necessary for JSelect

        $form->addDatePicker("date", "Ze dne:")
            ->addRule($form::FILLED, 'Zadejte datum')
            ->getControlPrototype()->class("form-control input-sm required")->placeholder("Datum");
        $form->addText("num", "Číslo d.:")
            ->setMaxLength(5)
            ->setRequired(FALSE)
            ->addRule($form::MAX_LENGTH, 'Maximální délka čísla dokladu je %d znaků', 5)
            ->addRule(
                $form::PATTERN,
                'Číslo dokladu musí být číslo, případně číslo s prefixem až 3 velkých písmen. Pro dělené doklady můžete použít číslo za / (např. V01/1)',
                ChitNumber::PATTERN
            )
            ->getControlPrototype()->placeholder("Číslo")
            ->class("form-control input-sm");
        $form->addText("purpose", "Účel výplaty:")
            ->setMaxLength(120)
            ->addRule($form::FILLED, 'Zadejte účel výplaty')
            ->getControlPrototype()->placeholder("Účel")
            ->class("form-control input-sm required");
        $form->addSelect("type", "Typ", ["in" => "Příjmy", "out" => "Výdaje"])
            ->setAttribute("size", "2")
            ->setDefaultValue("out")
            ->addRule($form::FILLED, "Vyberte typ");
        $form->addJSelect("category", "Kategorie", $form["type"], [$this, "getCategoriesByType"])
            ->setAttribute("class", "form-control input-sm");
        $form->addText("recipient", "Vyplaceno komu:")
            ->setMaxLength(64)
            ->setHtmlId("form-recipient")
            ->getControlPrototype()->class("form-control input-sm")->placeholder("Komu/Od");
        $form->addText("price", "Částka: ")
            ->setRequired('Musíte vyplnit částku')
            ->addRule(function(IControl $control) {
                try {
                    return new Amount($control->getValue());
                } catch (InvalidArgumentException $e) {
                    return FALSE;
                }
            }, 'Částka musí být větší než 0')
            ->setMaxLength(100)
            ->setHtmlId("form-out-price")
            ->getControlPrototype()->placeholder("Částka: 2+3*15")
            ->class("form-control input-sm");
        $form->addHidden("pid");
        $form->addSubmit('send', 'Uložit')
            ->setAttribute("class", "btn btn-primary");
        $form->onSuccess[] = function (BaseForm $form): void {
            $this->cashbookFormSubmitted($form);

        };
        return $form;
    }

    protected function createComponentCashbook(): CashbookControl
    {
        $cashbookId = $this->entityService->chits->getCashbookIdFromSkautisId($this->aid);

        return $this->cashbookFactory->create($cashbookId, $this->isEditable);
    }

    /**
     * přidává paragony všech kategorií
     */
    private function cashbookFormSubmitted(BaseForm $form): void
    {
        if ($form["send"]->isSubmittedBy()) {
            $this->editableOnly();
            $values = $form->getValues();

            $cashbookId = $this->entityService->chits->getCashbookIdFromSkautisId($this->aid);

            $number = $values->num !== '' ? new ChitNumber($values->num) : NULL;
            $date = Date::instance($values->date);
            $recipient = $values->recipient !== '' ? new Recipient($values->recipient) : NULL;
            $amount = new Amount($values->price);
            $purpose = $values->purpose;
            $category = $values->category;
            try {
                if ($values['pid'] != "") {//EDIT
                    $chitId = $values['pid'];
                    unset($values['id']);

                    $this->commandBus->handle(
                        new UpdateChit($cashbookId, $chitId, $number, $date, $recipient, $amount, $purpose, $category)
                    );

                    $this->flashMessage("Paragon byl upraven.");
                } else {//ADD
                    $this->commandBus->handle(
                        new AddChitToCashbook($cashbookId, $number, $date, $recipient, $amount, $purpose, $category)
                    );

                    $this->flashMessage("Paragon byl úspěšně přidán do seznamu.");
                }
                if ($this->entityService->chits->eventIsInMinus($this->getCurrentUnitId())) {
                    $this->flashMessage("Dostali jste se do záporné hodnoty.", "danger");
                }
            } catch (InvalidArgumentException | CashbookNotFoundException $exc) {
                $this->flashMessage("Paragon se nepodařilo přidat do seznamu.", "danger");
                $this->logger->error(sprintf("Can't add chit to cashbook (%s: %s)", get_class($exc), $exc->getMessage()));
            } catch (ChitLockedException $e) {
                $this->flashMessage('Nelze upravit zamčený paragon', 'error');
            } catch (\Skautis\Wsdl\WsdlException $se) {
                $this->flashMessage("Nepodařilo se upravit záznamy ve skautisu.", "danger");
            }

            $this->redirect("default", ["aid" => $this->aid]);
        }
    }

    /**
     * ověřuje editovatelnost paragonu a případně vrací chybovou hlášku rovnou
     * @param int $chitId
     * @throws \Nette\Application\AbortException
     */
    private function isChitEditable(int $chitId): void
    {
        $chit = $this->entityService->chits->get($chitId);
        if ($chit !== FALSE && is_null($chit->lock)) {
            return;
        }
        $this->flashMessage("Paragon není možné upravovat!", "danger");
        if ($this->isAjax()) {
            $this->sendPayload();
        } else {
            $this->redirect("this");
        }
    }

    public function fillTemplateVariables(): void
    {
        $this->template->object = $this->event;
        $this->template->cashbookId = $this->getCashbookId();
        try {
            $this->template->autoCompleter = array_values($this->memberService->getCombobox(FALSE, 15));
        } catch (\Skautis\Wsdl\WsdlException $e) {
            $this->template->autoCompleter = [];
        }
    }

    private function editChit(int $chitId): void
    {
        $this->isChitEditable($chitId);
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

    private function getCashbookId(): int
    {
        return $this->entityService->chits->getCashbookIdFromSkautisId($this->aid);
    }
}
