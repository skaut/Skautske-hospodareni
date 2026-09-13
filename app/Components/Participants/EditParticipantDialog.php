<?php

declare(strict_types=1);

namespace App\Components\Participants;

use App\Components\Dialog;
use App\Model\DTO\Participant\NonMemberParticipant;
use App\Model\DTO\Participant\Participant;
use App\Model\DTO\Participant\UpdateParticipant;
use App\Model\Participant\NonMemberParticipantService;
use Assert\Assertion;
use Cake\Chronos\ChronosDate;
use Closure;
use Component\Forms\BaseForm;
use LogicException;
use Nette\Application\Attributes\Persistent;

final class EditParticipantDialog extends Dialog
{
    #[Persistent]
    public ?int $participantId = null;

    /** @var Closure[] */
    public array $onUpdate = [];

    /** @param array<int, Participant> $participants */
    public function __construct(
        private array $participants,
        private bool $isAllowedDaysUpdate,
        private bool $isAccountAllowed,
        private bool $isRepaymentAllowed,
        private bool $isOnlineLogin,
        private bool $isAllowedUpdate,
        private NonMemberParticipantService $nonMemberParticipants,
    ) {
    }

    public function editParticipant(int $participantId): void
    {
        $this->participantId = $participantId;
        $this->show();
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->setFile(__DIR__.'/templates/EditParticipantDialog.latte');
    }

    protected function createComponentForm(): BaseForm
    {
        Assertion::notNull($this->participantId);
        Assertion::keyExists($this->participants, $this->participantId);

        $participant = $this->participants[$this->participantId];
        if (! $participant instanceof Participant) {
            throw new LogicException('Assertion failed.');
        }
        $form = new BaseForm();
        $nonMember = $participant->isNonMember()
            ? $this->nonMemberParticipants->get($participant->getPersonId())
            : null;

        if ($nonMember !== null) {
            $this->addNonMemberFields($form, $nonMember);
        }

        if ($this->isAllowedDaysUpdate) {
            $days = $form->addInteger('days', 'Počet dní')
                ->setRequired('Musíte vyplnit počet dní')
                ->addRule(BaseForm::MIN, 'Minimální počet dní je %d', 0)
                ->setDefaultValue($participant->getDays());
            if ($this->isOnlineLogin && ! $participant->isAccepted()) {
                $days->setRequired(false)
                    ->setDisabled()
                    ->getControlPrototype()
                    ->setAttribute('title', 'Nelze upravovat dny pro osoby, které nejsou přijaty přes e-přihlášku');
            }
        }

        $form->addText('payment', 'Částka')
            ->setRequired('Musíte vyplnit částku')
            ->addRule(BaseForm::MIN, 'Minimální částka je %d Kč', 0)
            ->setDefaultValue($participant->getPayment());

        if ($this->isRepaymentAllowed) {
            $form->addText('repayment', 'Vratka')
                ->setRequired(false)
                ->addRule(BaseForm::MIN, 'Minimální částka vratky je %d Kč', 0)
                ->setDefaultValue($participant->getRepayment());
        }

        if ($this->isAccountAllowed) {
            $form->addRadioList('isAccount', 'Na účet?', ['N' => 'Ne', 'Y' => 'Ano'])
                ->setDefaultValue($participant->getOnAccount());
        }

        $form->addSubmit('save', 'Upravit')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function ($_x, array $values) use ($participant, $nonMember): void {
            if (! $this->isAllowedUpdate) {
                $this->reload('Nemáte právo upravovat účastníky.', 'danger');
            }

            $changes = [];

            if ($values['payment'] !== $participant->getPayment()) {
                $changes[UpdateParticipant::FIELD_PAYMENT] = $values['payment'];
            }

            if ($this->isAllowedDaysUpdate && isset($values['days']) && $values['days'] !== $participant->getDays()) {
                $changes[UpdateParticipant::FIELD_DAYS] = $values['days'];
            }

            if ($this->isRepaymentAllowed && $values['repayment'] !== $participant->getRepayment()) {
                $changes[UpdateParticipant::FIELD_REPAYMENT] = $values['repayment'];
            }

            if ($this->isAccountAllowed && $values['isAccount'] !== $participant->getOnAccount()) {
                $changes[UpdateParticipant::FIELD_IS_ACCOUNT] = $values['isAccount'];
            }

            if ($nonMember !== null) {
                $this->nonMemberParticipants->update(
                    $participant->getPersonId(),
                    new NonMemberParticipant(
                        $values['firstName'],
                        $values['lastName'],
                        $values['nick'] === '' ? null : $values['nick'],
                        $values['sex'],
                        $values['birthday'] === null ? null : new ChronosDate($values['birthday']),
                        $values['street'],
                        $values['city'],
                        (int) $values['postcode'],
                    ),
                );
            }

            $this->onUpdate($this->participantId, $changes, $participant->isAccepted());
            $this->hide();
        };

        return $form;
    }

    private function addNonMemberFields(BaseForm $form, NonMemberParticipant $participant): void
    {
        $form->addText('firstName', 'Jméno')
            ->setRequired('Musíš vyplnit křestní jméno.')
            ->setDefaultValue($participant->getFirstName());

        $form->addText('lastName', 'Příjmení')
            ->setRequired('Musíš vyplnit příjmení.')
            ->setDefaultValue($participant->getLastName());

        $form->addText('street', 'Ulice')
            ->setRequired('Musíš vyplnit ulici.')
            ->setDefaultValue($participant->getStreet());

        $form->addText('city', 'Město')
            ->setRequired('Musíš vyplnit město.')
            ->setDefaultValue($participant->getCity());

        $form->addText('postcode', 'PSČ')
            ->setRequired('Musíš vyplnit PSČ.')
            ->setDefaultValue($participant->getPostcode());

        $form->addText('nick', 'Přezdívka')
            ->setDefaultValue($participant->getNickName());

        $form->addRadioList('sex', 'Pohlaví', [
            'male' => 'Muž',
            'female' => 'Žena',
        ])
            ->setRequired('Musíš vybrat pohlaví.')
            ->setDefaultValue($participant->getSex() === '' ? null : $participant->getSex());

        $form->addDate('birthday', 'Dat. nar.')
            ->setDefaultValue($participant->getBirthday());
    }
}
