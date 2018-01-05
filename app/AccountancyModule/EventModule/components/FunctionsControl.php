<?php

namespace App\AccountancyModule\EventModule\Components;

use App\AccountancyModule\Auth\Event;
use App\Forms\BaseForm;
use App\AccountancyModule\Auth\IAuthorizator;
use Model\Event\AssistantNotAdultException;
use Model\Event\Functions;
use Model\Event\LeaderNotAdultException;
use Model\EventEntity;
use Model\EventService;
use Model\MemberService;
use Nette\Application\UI\Control;
use Nette\Utils\ArrayHash;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Form;
use Skautis\Wsdl\PermissionException;

class FunctionsControl extends Control
{

    /** @var int */
    private $eventId;

    /** @var EventService */
    private $events;

    /** @var MemberService */
    private $members;

    /** @var IAuthorizator */
    private $authorizator;

    /**
     * @persistent
     * @var bool
     */
    public $editation = FALSE;

    public function __construct(int $eventId, EventEntity $eventEntity, MemberService $members, IAuthorizator $authorizator)
    {
        parent::__construct();
        $this->eventId = $eventId;
        $this->events = $eventEntity->event;
        $this->members = $members;
        $this->authorizator = $authorizator;
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
        return $this->authorizator->isAllowed(Event::UPDATE_FUNCTION, $this->eventId);
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

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();
        $personsOlderThan = $this->getPersonsOlderThan([15, 18]);

        $form->addSelect("leader", "Vedoucí", $personsOlderThan[18])
            ->setPrompt("")
            ->setAttribute("class", "combobox")
            ->setAttribute("data-autocomplete");

        $form->addSelect("assistant", "Zástupce", $personsOlderThan[18])
            ->setPrompt("")
            ->setAttribute("class", "combobox")
            ->setAttribute("data-autocomplete");

        $form->addSelect("accountant", "Hospodář", $personsOlderThan[15])
            ->setPrompt("")
            ->setAttribute("class", "combobox")
            ->setAttribute("data-autocomplete");

        $form->addSelect("medic", "Zdravotník", $personsOlderThan[15])
            ->setPrompt("")
            ->setAttribute("class", "combobox")
            ->setAttribute("data-autocomplete");

        $form->addSubmit("save", "Uložit")
            ->setAttribute("class", "btn btn-sm btn-primary ajax");

        $this->setDefaultValues($form);

        $form->onSuccess[] = function (BaseForm $form, ArrayHash $values) { $this->formSubmitted($form, $values); };
        return $form;
    }

    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/FunctionsControl.latte');
        $this->template->functions = $this->events->getFunctions($this->eventId);
        $this->template->editation = $this->editation;
        $this->template->canEdit = $this->canEdit();
        $this->template->render();
    }

    private function formSubmitted(BaseForm $form, ArrayHash $values): void
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
            $form->addError($exc->getMessage());
            $this->reload($exc->getMessage(), 'danger');
        } catch (LeaderNotAdultException $e) {
            $form->addError('Vedoucí akce musí být dosplělá osoba.');
            $this->reload();
        } catch (AssistantNotAdultException $e) {
            $form->addError('Zástupce musí být dosplělá osoba.');
            $this->reload();
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
            /** @var SelectBox $selectbox */
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
