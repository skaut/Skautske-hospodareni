<?php

namespace App\AccountancyModule\EventModule\Components;

use App\AccountancyModule\EventModule\EventPresenter;
use App\AccountancyModule\Factories\FormFactory;
use App\Forms\BaseForm;
use Model\Event\AssistantNotAdultException;
use Model\Event\Functions;
use Model\Event\LeaderNotAdultException;
use Model\EventEntity;
use Model\EventService;
use Model\MemberService;
use Nette\Application\UI\Control;
use Nette\ArrayHash;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Form;
use Skautis\Wsdl\PermissionException;

class FunctionsControl extends Control
{

    /** @var int */
    private $eventId;

    /** @var FormFactory */
    private $formFactory;

    /** @var EventService */
    private $events;

    /** @var MemberService */
    private $members;

    /**
     * @persistent
     * @var bool
     */
    public $editation = FALSE;

    public function __construct(int $eventId, FormFactory $formFactory, EventEntity $eventEntity, MemberService $members)
    {
        parent::__construct();
        $this->eventId = $eventId;
        $this->formFactory = $formFactory;
        $this->events = $eventEntity->event;
        $this->members = $members;
    }

    private function reload(?string $message = NULL, ?string $type = NULL) : void
    {
        if ($message !== NULL) {
            $this->presenter->flashMessage($message, $type);
        }
        if ($this->presenter->isAjax()) {
            $this->redrawControl();
        } else {
            $this->redirect('this');
        }
    }

    private function canEdit() : bool
    {
        /* @var $presenter EventPresenter */
        $presenter = $this->getPresenter();
        return $presenter->isAllowed('EV_EventGeneral_UPDATE_Function');
    }

    public function handleEdit() : void
    {
        $this->editation = $this->canEdit();
        $this->reload();
    }

    public function handleCloseEditation() : void
    {
        $this->editation = FALSE;
        $this->reload();
    }

    private function createForm(int $functionId, int $minimalAge) : Form
    {
        $form = $this->formFactory->create(TRUE);

        $functions = $this->events->getFunctions($this->eventId);
        $combo = $this->members->getCombobox(FALSE, $minimalAge);

        $selectedPerson = array_key_exists($functions[$functionId]->ID_Person, $combo)
            ? $functions[$functionId]->ID_Person
            : NULL;

        $form->addSelect("person", NULL, $combo)
            ->setPrompt("")
            ->setDefaultValue($selectedPerson)
            ->setAttribute('class', 'combobox')
            ->setAttribute('data-autocomplete');
        $form->addSubmit('send', 'Nastavit')
            ->setAttribute("class", "btn btn-sm btn-primary ajax");

        $form->onSuccess[] = function ($form, $values) use ($functionId) {
            if (!$this->canEdit()) {
                $this->reload('Nemáte oprávnění upravit vedení akce', 'danger');
            }
            try {
                $this->events->setFunction($this->eventId, $values->person, $functionId);
                $this->handleCloseEditation();
                $this->reload('Funkce uložena.', 'success');
                return;
            } catch (PermissionException $exc) {
                $this->reload($exc->getMessage(), 'danger');
            } catch (LeaderNotAdultException $e) {
                $this->reload('Vedoucí akce musí být dosplělá osoba.', 'danger');
            } catch (AssistantNotAdultException $e) {
                $this->reload('Zástupce musí být dosplělá osoba.', 'danger');
            }
            $this->reload('Nepodařilo se upravit funkci', 'danger');
        };
        return $form;
    }

    public function handleRemoveFunction($functionId) : void
    {
        if (!$this->canEdit()) {
            $this->reload('Nemáte oprávnění upravit vedení akce', 'danger');
        }

        if (!$this->events->setFunction($this->eventId, NULL, $functionId)) {
            $this->reload('Funkci se nepodařilo odebrat', 'danger');
        }
        $this->reload();
    }

    protected function createComponentLeaderForm() : Form
    {
        return $this->createForm(0, 18);
    }

    protected function createComponentAssistantForm() : Form
    {
        return $this->createForm(1, 18);
    }

    protected function createComponentAccountantForm() : Form
    {
        return $this->createForm(2, 15);
    }

    protected function createComponentMedicForm() : Form
    {
        return $this->createForm(3, 15);
    }

    protected function createComponentForm()
    {
        $form = $this->formFactory->create();
        $personsOlderThan = $this->getPersonsOlderThan([15, 18]);

        $form->addSelect("leader", "Vedoucí akce", $personsOlderThan[18])
            ->setPrompt("")
            ->setAttribute("class", "combobox")
            ->setAttribute("data-autocomplete")
            ->setRequired("Musíte vyplnit vedoucího akce");

        $form->addSelect("assistant", "Zástupce vedoucího akce", $personsOlderThan[18])
            ->setPrompt("")
            ->setAttribute("class", "combobox")
            ->setAttribute("data-autocomplete");

        $form->addSelect("accountant", "Hospodář akce", $personsOlderThan[15])
            ->setPrompt("")
            ->setAttribute("class", "combobox")
            ->setAttribute("data-autocomplete");

        $form->addSelect("medic", "Zdravotník akce", $personsOlderThan[15])
            ->setPrompt("")
            ->setAttribute("class", "combobox")
            ->setAttribute("data-autocomplete");

        $form->addSubmit("send", "Uložit")
            ->setAttribute("class", "btn btn-sm btn-primary ajax");

        $this->setDefaultValues($form);

        $form->onSuccess[] = function ($form, ArrayHash $values) { $this->formSubmitted($values); };
        return $form;
    }

    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/FunctionsControl.latte');
        $this->template->functions = $this->events->getFunctions($this->eventId);
        $this->template->editation = $this->editation;
        $this->template->render();
    }

    private function formSubmitted(ArrayHash $values): void
    {
        if (!$this->canEdit()) {
            $this->reload('Nemáte oprávnění upravit vedení akce', 'danger');
        }
        try {
            $this->events->updateFunctions($this->eventId,
                new Functions($values->leader, $values->assistant, $values->accountant, $values->medic)
            );
            $this->handleCloseEditation();
            $this->reload('Funkce uloženy.', 'success');
            return;
        } catch (PermissionException $exc) {
            $this->reload($exc->getMessage(), 'danger');
        } catch (LeaderNotAdultException $e) {
            $this->reload('Vedoucí akce musí být dosplělá osoba.', 'danger');
        } catch (AssistantNotAdultException $e) {
            $this->reload('Zástupce musí být dosplělá osoba.', 'danger');
        }
        $this->reload('Nepodařilo se upravit funkce', 'danger');
    }

    private function setDefaultValues(Form $form): void
    {
        $selected = $this->events->getSelectedFunctions($this->eventId);

        $values = [
            "leader" => $selected->getLeaderId(),
            "assistant" => $selected->getAssistantId(),
            "accountant" => $selected->getAccountantId(),
            "medic" => $selected->getMedicId(),
        ];

        foreach($values as $functionName => $personId) {
            /* @var $selectbox SelectBox */
            $selectbox = $form[$functionName];
            $selectbox->setDefaultValue(isset($selectbox->getItems()[$personId]) ? $personId : NULL);
        }
    }

    /**
     * @param int[] $ages
     * @return array - [age => [person id => name], ...]
     */
    private function getPersonsOlderThan(array $ages): array
    {
        $persons = [];
        foreach($ages as $age) {
            $persons[$age] = $this->members->getCombobox(FALSE, $age);
        }

        return $persons;
    }

}
