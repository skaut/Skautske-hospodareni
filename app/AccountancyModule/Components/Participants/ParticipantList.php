<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\Participants;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use Model\DTO\Participant\Participant;
use Model\DTO\Participant\UpdateParticipant;
use Model\EventEntity;
use Nette\Application\BadRequestException;
use Nette\Forms\Controls\SubmitButton;
use function array_filter;
use function array_map;
use function sprintf;
use function usort;

/**
 * @method void onUpdate(UpdateParticipant[] $personIds)
 * @method void onRemove(int[] $participantIds)
 */
final class ParticipantList extends BaseControl
{
    private const SORT_OPTIONS = [
        'displayName' => 'Jméno',
        'unitRegistrationNumber' => 'Jednotka',
        'onAccount' => 'Na účet?',
        'days' => 'Dnů',
        'payment' => 'Částka',
        'repayment' => 'Vratka',
        'birthday' => 'Věk',
    ];

    private const DEFAULT_SORT = 'displayName';

    /** @var int */
    public $aid;

    /** @var callable[] */
    public $onUpdate = [];

    /** @var callable[] */
    public $onRemove = [];

    /** @var bool */
    protected $isAllowRepayment;

    /** @var bool */
    protected $isAllowIsAccount;

    /** @var bool */
    protected $isAllowParticipantUpdate;

    /** @var bool */
    protected $isAllowParticipantDelete;

    /** @var EventEntity */
    protected $eventService;

    /** @var Participant[] */
    private $currentParticipants;

    /**
     * @var bool
     * @persistent
     */
    public $showUnits = false;

    /**
     * @var string|null
     * @persistent
     */
    public $sort = 'displayName';

    /**
     * @param Participant[] $currentParticipants
     */
    public function __construct(
        int $aid,
        EventEntity $eventService,
        array $currentParticipants,
        bool $isAllowRepayment,
        bool $isAllowIsAccount,
        bool $isAllowParticipantUpdate,
        bool $isAllowParticipantDelete
    ) {
        parent::__construct();
        $this->aid                      = $aid;
        $this->eventService             = $eventService;
        $this->currentParticipants      = $currentParticipants;
        $this->isAllowRepayment         = $isAllowRepayment;
        $this->isAllowIsAccount         = $isAllowIsAccount;
        $this->isAllowParticipantUpdate = $isAllowParticipantUpdate;
        $this->isAllowParticipantDelete = $isAllowParticipantDelete;
    }

    public function render() : void
    {
        $this->redrawControl(); // Always redraw

        $this->sortParticipants($this->currentParticipants, $this->sort ?? self::DEFAULT_SORT);

        $sortOptions = self::SORT_OPTIONS;
        if (! $this->isAllowRepayment) {
            unset($sortOptions['repayment']);
        }
        if (! $this->isAllowIsAccount) {
            unset($sortOptions['onAccount']);
        }

        $this->template->setFile(__DIR__ . '/templates/ParticipantList.latte');
        $this->template->setParameters([
            'aid' => $this->aid,
            'participants' => $this->currentParticipants,
            'sort'       => $this->sort,
            'sortOptions' => $sortOptions,
            'showUnits' => $this->showUnits,
            'isAllowRepayment' => $this->isAllowRepayment,
            'isAllowIsAccount' => $this->isAllowIsAccount,
            'isAllowParticipantUpdate' => $this->isAllowParticipantUpdate,
            'isAllowParticipantDelete' => $this->isAllowParticipantDelete,
        ]);

        $this->template->render();
    }

    /**
     * @param Participant[] $participants
     */
    protected function sortParticipants(array &$participants, string $sort) : void
    {
        if (! isset(self::SORT_OPTIONS[$sort])) {
            throw new BadRequestException(sprintf('Unknown sort option "%s"', $sort), 400);
        }

        usort($participants, fn(Participant $a, Participant $b) => $a->{$sort} <=> $b->{$sort});
    }

    public function handleSort(string $sort) : void
    {
        $this->sort = $sort;
        if ($this->getPresenter()->isAjax()) {
            $this->redrawControl('participants');
        } else {
            $this->redirect('this');
        }
    }

    public function handleShowUnits(bool $units) : void
    {
        $this->showUnits = $units;
        if ($this->getPresenter()->isAjax()) {
            $this->redrawControl('participants');
        } else {
            $this->redirect('this');
        }
    }

    public function handleRemove(int $participantId) : void
    {
        if (! $this->isAllowParticipantDelete) {
            $this->reload('Nemáte právo mazat účastníky.', 'danger');
        }
        $this->onRemove([$participantId]);
        $this->currentParticipants = array_filter(
            $this->currentParticipants,
            function (Participant $p) use ($participantId) {
                return $p->getId() !== $participantId;
            }
        );
        $this->reload('Účastník byl odebrán', 'success');
    }

    public function createComponentFormMassParticipants() : BaseForm
    {
        $form = new BaseForm();

        $editCon = $form->addContainer('edit');

        $editCon->addText('days', 'Dní')
            ->setNullable()
            ->setAttribute('placeholder', 'Ponechat původní hodnotu');

        $editCon->addText('payment', 'Částka')
            ->setNullable()
            ->setAttribute('placeholder', 'Ponechat původní hodnotu');

        $editCon->addText('repayment', 'Vratka')
            ->setNullable()
            ->setAttribute('placeholder', 'Ponechat původní hodnotu');

        $form->addCheckboxList('participantIds', null, array_map(fn() => '', $this->participantsById()))
            ->setRequired('Musíte vybrat některého z účastníků');

        $editCon->addRadioList('isAccount', 'Na účet?', ['N' => 'Ne', 'Y' => 'Ano', '' => 'Ponechat původní hodnotu'])
            ->setDefaultValue('');
        $editCon->addSubmit('send', 'Upravit')
            ->setAttribute('class', 'btn btn-info btn-small')
            ->onClick[] = fn(SubmitButton $button) => $this->massEditSubmitted($button);

        $form->addSubmit('send', 'Odebrat vybrané')
            ->onClick[] = fn(SubmitButton $button) => $this->massRemoveSubmitted($button);

        return $form;
    }

    private function massEditSubmitted(SubmitButton $button) : void
    {
        if (! $this->isAllowParticipantUpdate) {
            $this->flashMessage('Nemáte právo upravovat účastníky.', 'danger');
            $this->redirect('Default:');
        }

        $values = $button->getForm()->getValues()['edit'];

        $changes = [];
        foreach ($button->getForm()->getValues()->participantIds as $participantId) {
            if ($values['days'] !== null) {
                $changes[] = new UpdateParticipant($this->aid, $participantId, UpdateParticipant::FIELD_DAYS, $values['days']);
            }
            if ($values['payment'] !== null) {
                $changes[] = new UpdateParticipant($this->aid, $participantId, UpdateParticipant::FIELD_PAYMENT, $values['payment']);
            }
            if ($values['repayment'] !== null) {
                $changes[] = new UpdateParticipant($this->aid, $participantId, UpdateParticipant::FIELD_REPAYMENT, $values['repayment']);
            }
            if ($values['isAccount'] === '') {
                continue;
            }

            $changes[] = new UpdateParticipant($this->aid, $participantId, UpdateParticipant::FIELD_IS_ACCOUNT, $values['isAccount']);
        }

        $this->onUpdate($changes);
        $this->reload('Účastníci byli upraveni.');
    }

    private function massRemoveSubmitted(SubmitButton $button) : void
    {
        if (! $this->isAllowParticipantDelete) {
            $this->flashMessage('Nemáte právo mazat účastníky.', 'danger');
            $this->redirect('Default:');
        }

        $ids = [];
        foreach ($button->getForm()->getValues()->participantIds as $participantId) {
            $ids[] = $participantId;
        }
        $this->onRemove($ids);
        $this->reload('Účastníci byli odebráni');
    }

    /**
     * @return array<int, Participant> Participant's indexed by their ID
     */
    private function participantsById() : array
    {
        $participants = [];

        foreach ($this->currentParticipants as $participant) {
            $participants[$participant->getId()] = $participant;
        }

        return $participants;
    }
}
