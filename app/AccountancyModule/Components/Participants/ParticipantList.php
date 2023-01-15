<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\Participants;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use Model\DTO\Participant\Participant;
use Model\DTO\Participant\UpdateParticipant;
use Nette\Application\BadRequestException;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\IResponse;

use function array_filter;
use function array_map;
use function in_array;
use function sprintf;
use function strcoll;
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

    private const NO_ACTION = '';

    /** @var callable[] */
    public array $onUpdate = [];

    /** @var callable[] */
    public array $onRemove = [];

    /** @persistent */
    public bool $showUnits = false;

    /** @persistent */
    public string|null $sort = 'displayName';

    /** @param Participant[] $currentParticipants */
    public function __construct(
        public int $aid,
        private array $currentParticipants,
        protected bool $isAllowDaysUpdate,
        protected bool $isAllowRepayment,
        protected bool $isAllowIsAccount,
        protected bool $isAllowParticipantUpdate,
        protected bool $isAllowParticipantDelete,
    ) {
    }

    public function render(): void
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
            'isAllowDaysUpdate' => $this->isAllowDaysUpdate,
            'isAllowRepayment' => $this->isAllowRepayment,
            'isAllowIsAccount' => $this->isAllowIsAccount,
            'isAllowParticipantUpdate' => $this->isAllowParticipantUpdate,
            'isAllowParticipantDelete' => $this->isAllowParticipantDelete,
            'isAllowAnyAction' => $this->isAllowParticipantUpdate || $this->isAllowParticipantDelete,
        ]);

        $this->template->render();
    }

    /** @param Participant[] $participants */
    protected function sortParticipants(array &$participants, string $sort): void
    {
        if (! isset(self::SORT_OPTIONS[$sort])) {
            throw new BadRequestException(sprintf('Unknown sort option "%s"', $sort), 400);
        }

        if ($sort === 'displayName') {
            $sortFunction = fn (Participant $a, Participant $b) => strcoll($a->{$sort}, $b->{$sort});
        } else {
            $sortFunction = fn (Participant $a, Participant $b) => $a->{$sort} <=> $b->{$sort};
        }

        usort($participants, $sortFunction);
    }

    public function handleSort(string $sort): void
    {
        $this->sort = $sort;
        if ($this->getPresenter()->isAjax()) {
            $this->redrawControl('participants');
        } else {
            $this->redirect('this');
        }
    }

    public function handleShowUnits(bool $units): void
    {
        $this->showUnits = $units;
        if ($this->getPresenter()->isAjax()) {
            $this->redrawControl('participants');
        } else {
            $this->redirect('this');
        }
    }

    public function handleRemove(int $participantId): void
    {
        if (! $this->isAllowParticipantDelete) {
            $this->reload('Nemáte právo mazat účastníky.', 'danger');
        }

        $this->onRemove([$participantId]);
        $this->currentParticipants = array_filter(
            $this->currentParticipants,
            function (Participant $p) use ($participantId) {
                return $p->getId() !== $participantId;
            },
        );
        $this->reload('Účastník byl odebrán', 'success');
    }

    public function handleEdit(int $participantId): void
    {
        if (! isset($this->participantsById()[$participantId])) {
            throw new BadRequestException(
                sprintf('Participant %d does not exist', $participantId),
                IResponse::S404_NOT_FOUND,
            );
        }

        $this['editDialog']->editParticipant($participantId);
    }

    protected function createComponentEditDialog(): EditParticipantDialog
    {
        $dialog = new EditParticipantDialog($this->participantsById(), $this->isAllowDaysUpdate, $this->isAllowIsAccount, $this->isAllowRepayment);

        $dialog->onUpdate[] = function (int $participantId, array $fields): void {
            $changes = [];

            foreach ($fields as $field => $value) {
                $changes[] = new UpdateParticipant($this->aid, $participantId, $field, (string) $value);
            }

            $this->onUpdate($changes);
            $this->reload('Účastník byl upraven.', 'success');
        };

        return $dialog;
    }

    public function createComponentFormMassParticipants(): BaseForm
    {
        $form = new BaseForm();

        $editCon = $form->addContainer('edit');

        $editCon->addText('days', 'Dní')
            ->setNullable()
            ->setHtmlAttribute('placeholder', 'Ponechat původní hodnotu');

        $editCon->addText('payment', 'Částka')
            ->setNullable()
            ->setHtmlAttribute('placeholder', 'Ponechat původní hodnotu');

        $editCon->addText('repayment', 'Vratka')
            ->setNullable()
            ->setHtmlAttribute('placeholder', 'Ponechat původní hodnotu');

        $form->addCheckboxList('participantIds', null, array_map(fn () => '', $this->participantsById()))
            ->setRequired('Musíte vybrat některého z účastníků');

        $editCon->addRadioList('isAccount', 'Na účet?', ['N' => 'Ne', 'Y' => 'Ano', self::NO_ACTION => 'Ponechat původní hodnotu'])
            ->setDefaultValue('');
        $editCon->addSubmit('send', 'Upravit')
            ->setHtmlAttribute('class', 'btn btn-info btn-small')
            ->onClick[] = function (SubmitButton $button): void {
                $this->massEditSubmitted($button);
            };

        $form->addSubmit('send', 'Odebrat vybrané')
            ->onClick[] = function (SubmitButton $button): void {
                $this->massRemoveSubmitted($button);
            };

        return $form;
    }

    private function massEditSubmitted(SubmitButton $button): void
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

            if (in_array($values['isAccount'], [self::NO_ACTION, null])) {
                continue;
            }

            $changes[] = new UpdateParticipant($this->aid, $participantId, UpdateParticipant::FIELD_IS_ACCOUNT, $values['isAccount']);
        }

        $this->onUpdate($changes);
        $this->reload('Účastníci byli upraveni.');
    }

    private function massRemoveSubmitted(SubmitButton $button): void
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

    /** @return array<int, Participant> Participant's indexed by their ID */
    private function participantsById(): array
    {
        $participants = [];

        foreach ($this->currentParticipants as $participant) {
            $participants[$participant->getId()] = $participant;
        }

        return $participants;
    }
}
