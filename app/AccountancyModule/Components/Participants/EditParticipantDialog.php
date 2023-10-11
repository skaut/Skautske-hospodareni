<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\Participants;

use App\AccountancyModule\Components\Dialog;
use App\Forms\BaseForm;
use Assert\Assertion;
use Closure;
use Model\DTO\Participant\Participant;
use Model\DTO\Participant\UpdateParticipant;

use function assert;

final class EditParticipantDialog extends Dialog
{
    /** @persistent */
    public int|null $participantId = null;

    /** @var Closure[] */
    public array $onUpdate = [];

    /** @param array<int, Participant> $participants */
    public function __construct(private array $participants, private bool $isAllowedDaysUpdate, private bool $isAccountAllowed, private bool $isRepaymentAllowed, private bool $isOnlineLogin)
    {
    }

    public function editParticipant(int $participantId): void
    {
        $this->participantId = $participantId;
        $this->show();
    }

    protected function beforeRender(): void
    {
        $this->template->setFile(__DIR__ . '/templates/EditParticipantDialog.latte');
    }

    protected function createComponentForm(): BaseForm
    {
        Assertion::notNull($this->participantId);
        Assertion::keyExists($this->participants, $this->participantId);

        $participant = $this->participants[$this->participantId];
        assert($participant instanceof Participant);

        $form = new BaseForm();

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

        $form->onSuccess[] = function ($_x, array $values) use ($participant): void {
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

            $this->onUpdate($this->participantId, $changes, $participant->isAccepted());
            $this->hide();
        };

        return $form;
    }
}
