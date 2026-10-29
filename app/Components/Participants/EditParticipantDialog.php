<?php

declare(strict_types=1);

namespace App\Components\Participants;

use App\Components\Dialog;
use App\Components\Payment\PaymentFormFields;
use App\Model\DTO\Participant\NonMemberParticipant;
use App\Model\DTO\Participant\Participant;
use App\Model\DTO\Participant\UpdateParticipant;
use App\Model\Participant\NonMemberParticipantService;
use App\Model\Participant\PersonAccessNotAllowed;
use App\Model\Participant\PersonAddressChangeNotAllowed;
use App\Model\Utils\MoneyFactory;
use Assert\Assertion;
use Cake\Chronos\ChronosDate;
use Closure;
use Component\Forms\BaseForm;
use LogicException;
use Nette\Application\Attributes\Persistent;
use Nette\Forms\Control;
use Nette\Forms\Controls\SubmitButton;

final class EditParticipantDialog extends Dialog
{
    public const ADDRESS_CHANGE_NOT_ALLOWED_MESSAGE = 'SkautIS nedovoluje změnit adresu tohoto účastníka, protože tam má plný osobní záznam '
        .'(například z dřívějšího členství).';
    public const PERSON_UPDATE_NOT_ALLOWED_MESSAGE = 'Nemáte ve skautISu oprávnění upravovat osobní údaje tohoto účastníka.';

    private const SAVE_WITHOUT_PERSONAL_DATA = 'saveWithoutPersonalData';

    #[Persistent]
    public ?int $participantId = null;

    /** @var Closure[] */
    public array $onUpdate = [];

    private ?NonMemberParticipant $nonMember = null;

    private bool $isPersonalDataNotAccessible = false;

    private ?string $personalDataSaveError = null;

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
        $this->template->customClasses = 'modal-dialog-centered modal-dialog-scrollable';

        $missingPersonalData = null;
        if ($this->opened && $this->participantId !== null) {
            $form = $this['form']; // Načte osobní údaje, podle kterých se ukazuje upozornění.
            $missingPersonalData = $this->nonMember === null ? null : MissingPersonalData::describe($this->nonMember);

            // Uložení bez osobních údajů se nabízí jen spolu s upozorněním, proč osobní údaje nejde uložit.
            $saveWithoutPersonalData = $form->getComponent(self::SAVE_WITHOUT_PERSONAL_DATA, false);
            if ($saveWithoutPersonalData !== null && $missingPersonalData === null && $this->personalDataSaveError === null) {
                $form->removeComponent($saveWithoutPersonalData);
            }
        }

        $this->template->setParameters([
            'isPersonalDataNotAccessible' => $this->isPersonalDataNotAccessible,
            'missingPersonalData' => $missingPersonalData,
            'personalDataSaveError' => $this->personalDataSaveError,
        ]);
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
        $form->getElementPrototype()->addClass('inline-errors');
        $nonMember = null;
        if ($participant->isNonMember()) {
            try {
                $nonMember = $this->nonMemberParticipants->get($participant->getPersonId());
            } catch (PersonAccessNotAllowed) {
                $this->isPersonalDataNotAccessible = true;
            }
        }

        $this->nonMember = $nonMember;

        if ($nonMember !== null) {
            $this->addNonMemberFields($form, $nonMember);
        }

        /** @var list<Control> $participationControls */
        $participationControls = [];

        if ($this->isAllowedDaysUpdate) {
            $participationControls[] = $days = $form->addInteger('days', 'Počet dní')
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

        $participationControls[] = $form->addText('payment', 'Částka')
            ->setRequired('Musíte vyplnit částku')
            ->addRule(BaseForm::FLOAT, 'Částka musí být zadaná jako číslo')
            ->addRule(BaseForm::PATTERN, PaymentFormFields::MONEY_PATTERN_MESSAGE, PaymentFormFields::MONEY_PATTERN)
            ->addRule(BaseForm::MIN, 'Minimální částka je %d Kč', 0)
            ->setDefaultValue(MoneyFactory::toDecimal($participant->getPayment()));

        if ($this->isRepaymentAllowed) {
            $participationControls[] = $form->addText('repayment', 'Vratka')
                ->setRequired(false)
                ->addRule(BaseForm::FLOAT, 'Vratka musí být zadaná jako číslo')
                ->addRule(BaseForm::PATTERN, PaymentFormFields::MONEY_PATTERN_MESSAGE, PaymentFormFields::MONEY_PATTERN)
                ->addRule(BaseForm::MIN, 'Minimální částka vratky je %d Kč', 0)
                ->setDefaultValue(MoneyFactory::toDecimal($participant->getRepayment()));
        }

        if ($this->isAccountAllowed) {
            $participationControls[] = $form->addRadioList('isAccount', 'Na účet?', ['N' => 'Ne', 'Y' => 'Ano'])
                ->setDefaultValue($participant->getOnAccount());
        }

        $form->addSubmit('save', 'Upravit')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $saveWithoutPersonalData = null;
        if ($nonMember !== null) {
            // Rozsah validace musí zahrnout i CSRF ochranu, jinak by ji Nette pro toto tlačítko nekontrolovalo.
            $saveWithoutPersonalData = $form->addSubmit(self::SAVE_WITHOUT_PERSONAL_DATA, 'Přesto uložit bez osobních údajů')
                ->setValidationScope([...$participationControls, BaseForm::ProtectorId])
                ->setHtmlAttribute('class', 'btn btn-outline-danger');
        }

        $form->onSuccess[] = function (BaseForm $form, array $values) use ($participant, $nonMember, $saveWithoutPersonalData): void {
            if (! $this->isAllowedUpdate) {
                $this->reload('Nemáte právo upravovat účastníky.', 'danger');
            }

            $withoutPersonalData = $saveWithoutPersonalData instanceof SubmitButton && $saveWithoutPersonalData->isSubmittedBy();

            if ($nonMember !== null && ! $withoutPersonalData) {
                $updatedNonMember = $this->personalData($values);

                try {
                    if (! $updatedNonMember->equals($nonMember)) {
                        $this->nonMemberParticipants->update($participant->getPersonId(), $updatedNonMember);
                    }
                } catch (PersonAddressChangeNotAllowed) {
                    $this->showPersonalDataSaveError(self::ADDRESS_CHANGE_NOT_ALLOWED_MESSAGE);

                    return;
                } catch (PersonAccessNotAllowed) {
                    $this->showPersonalDataSaveError(self::PERSON_UPDATE_NOT_ALLOWED_MESSAGE);

                    return;
                }
            }

            $this->onUpdate($this->participantId, $this->participationChanges($participant, $values), $participant->isAccepted(), $withoutPersonalData);
            $this->hide();
        };

        return $form;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, int|string>
     */
    private function participationChanges(Participant $participant, array $values): array
    {
        $changes = [];

        $payment = MoneyFactory::fromDecimal((string) $values['payment']);
        if (! $payment->equals($participant->getPayment())) {
            $changes[UpdateParticipant::FIELD_PAYMENT] = MoneyFactory::toDecimal($payment);
        }

        if ($this->isAllowedDaysUpdate && isset($values['days']) && $values['days'] !== $participant->getDays()) {
            $changes[UpdateParticipant::FIELD_DAYS] = $values['days'];
        }

        if ($this->isRepaymentAllowed) {
            $repayment = $values['repayment'] === null || $values['repayment'] === ''
                ? MoneyFactory::zero()
                : MoneyFactory::fromDecimal((string) $values['repayment']);

            if (! $repayment->equals($participant->getRepayment())) {
                $changes[UpdateParticipant::FIELD_REPAYMENT] = MoneyFactory::toDecimal($repayment);
            }
        }

        if ($this->isAccountAllowed && $values['isAccount'] !== $participant->getOnAccount()) {
            $changes[UpdateParticipant::FIELD_IS_ACCOUNT] = $values['isAccount'];
        }

        return $changes;
    }

    /** @param array<string, mixed> $values */
    private function personalData(array $values): NonMemberParticipant
    {
        return new NonMemberParticipant(
            $values['firstName'],
            $values['lastName'],
            $values['nick'] === '' ? null : $values['nick'],
            $values['sex'],
            $values['birthday'] === null ? null : new ChronosDate($values['birthday']),
            $values['street'],
            $values['city'],
            (string) $values['postcode'],
        );
    }

    private function showPersonalDataSaveError(string $message): void
    {
        $this->personalDataSaveError = $message;
        $this->redrawControl();
    }

    private function addNonMemberFields(BaseForm $form, NonMemberParticipant $participant): void
    {
        $form->addText('firstName', 'Jméno')
            ->setRequired('Musíte vyplnit křestní jméno.')
            ->setDefaultValue($participant->getFirstName());

        $form->addText('lastName', 'Příjmení')
            ->setRequired('Musíte vyplnit příjmení.')
            ->setDefaultValue($participant->getLastName());

        $form->addText('street', 'Ulice')
            ->setRequired('Musíte vyplnit ulici.')
            ->setDefaultValue($participant->getStreet());

        $form->addText('city', 'Město')
            ->setRequired('Musíte vyplnit město.')
            ->setDefaultValue($participant->getCity());

        $form->addText('postcode', 'PSČ')
            ->setRequired('Musíte vyplnit PSČ.')
            ->setDefaultValue($participant->getPostcode());

        $form->addText('nick', 'Přezdívka')
            ->setDefaultValue($participant->getNickName());

        $form->addRadioList('sex', 'Pohlaví', [
            'male' => 'Muž',
            'female' => 'Žena',
        ])
            ->setRequired('Musíte vybrat pohlaví.')
            ->setDefaultValue($participant->getSex() === '' ? null : $participant->getSex());

        $form->addDate('birthday', 'Dat. nar.')
            ->setDefaultValue($participant->getBirthday());
    }
}
