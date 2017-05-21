<?php

namespace App\AccountancyModule\EventModule\Components;

use App\AccountancyModule\EventModule\EventPresenter;
use App\AccountancyModule\Factories\FormFactory;
use Model\Event\AssistantNotAdultException;
use Model\Event\LeaderNotAdultException;
use Model\EventEntity;
use Model\EventService;
use Model\MemberService;
use Nette\Application\UI\Control;
use Nette\Forms\Form;
use Skautis\Wsdl\PermissionException;

class Functions extends Control
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

    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/Functions.latte');
        $this->template->functions = $this->events->getFunctions($this->eventId);
        $this->template->editation = $this->editation;
        $this->template->render();
    }

}
