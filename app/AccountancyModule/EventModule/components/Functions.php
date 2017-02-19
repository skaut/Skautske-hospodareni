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

    /** @persistent */
    public $editation = FALSE;

    /**
     * FunctionsForm constructor.
     * @param int $eventId
     * @param FormFactory $formFactory
     * @param EventEntity $eventEntity
     * @param MemberService $members
     */
    public function __construct($eventId, FormFactory $formFactory, EventEntity $eventEntity, MemberService $members)
    {
        $this->eventId = $eventId;
        $this->formFactory = $formFactory;
        $this->events = $eventEntity->event;
        $this->members = $members;
    }

    private function reload($message = NULL, $type = NULL)
    {
        if ($message) {
            $this->presenter->flashMessage($message, $type);
        }
        if ($this->presenter->isAjax()) {
            $this->redrawControl();
        } else {
            $this->redirect('this');
        }
    }

    /**
     * @return bool
     */
    private function canEdit()
    {
        /* @var $presenter EventPresenter */
        $presenter = $this->getPresenter();
        return $presenter->isAllowed('EV_EventGeneral_UPDATE_Function');
    }

    public function handleEdit()
    {
        $this->editation = $this->canEdit();
        $this->reload();
    }

    public function handleCloseEditation()
    {
        $this->editation = FALSE;
        $this->reload();
    }

    /**
     * @param string $functionId
     * @param int $minimalAge
     * @return Form
     */
    private function createForm($functionId, $minimalAge)
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

    public function handleRemoveFunction($functionId)
    {
        if (!$this->canEdit()) {
            $this->reload('Nemáte oprávnění upravit vedení akce', 'danger');
        }

        if (!$this->events->setFunction($this->eventId, NULL, $functionId)) {
            $this->reload('Funkci se nepodařilo odebrat', 'danger');
        }
        $this->reload();
    }

    protected function createComponentLeaderForm()
    {
        return $this->createForm(0, 18);
    }

    protected function createComponentAssistantForm()
    {
        return $this->createForm(1, 18);
    }

    protected function createComponentAccountantForm()
    {
        return $this->createForm(2, 15);
    }

    protected function createComponentMedicForm()
    {
        return $this->createForm(3, 15);
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/Functions.latte');
        $this->template->functions = $this->events->getFunctions($this->eventId);
        $this->template->editation = $this->editation;
        $this->template->render();
    }

}
