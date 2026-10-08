<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\Dialog;
use App\Model\Common\EmailAddress;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Payment\MemberEmail;
use App\Model\DTO\Payment\MemberEmailType;
use App\Model\DTO\Payment\Payment;
use App\Model\Payment\Commands\Payment\UpdatePaymentEmailRecipients;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\MemberEmailsQuery;
use Component\Forms\BaseForm;
use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function explode;
use function implode;
use function in_array;

/** @method void onSuccess() */
final class BulkEmailRecipientsDialog extends Dialog
{
    private const OPERATION_ADD = 'add';
    private const OPERATION_REMOVE = 'remove';

    /** @var callable[] */
    public array $onSuccess = [];

    /** Comma-separated payment IDs because persistent parameters do not support arrays. */
    #[Persistent]
    public string $paymentIds = '';

    public function __construct(
        private int $groupId,
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private PaymentService $paymentService,
    ) {
    }

    /** @param int[] $paymentIds */
    public function open(array $paymentIds): void
    {
        $this->paymentIds = implode(',', array_unique(array_map('intval', $paymentIds)));
        $this->show();
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->setFile(__DIR__.'/templates/BulkEmailRecipientsDialog.latte');
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addRadioList('operation', 'Úprava', [
            self::OPERATION_ADD => 'Přidat kontakty ze SkautIS',
            self::OPERATION_REMOVE => 'Odebrat kontakty ze SkautIS',
        ])
            ->setDefaultValue(self::OPERATION_ADD)
            ->setRequired('Vyberte způsob úpravy.');
        $form->addCheckboxList('categories', 'Kategorie kontaktů', $this->categoryOptions())
            ->setRequired('Vyberte alespoň jednu kategorii kontaktů.');
        $form->addSubmit('send', 'Upravit e-maily')
            ->setHtmlAttribute('class', 'ajax btn btn-primary');

        $form->onSubmit[] = function (): void {
            $this->redrawControl();
        };
        $form->onSuccess[] = function (Form $form): void {
            $this->updateRecipients($form, $form->getValues(ArrayHash::class));
        };

        return $form;
    }

    private function updateRecipients(Form $form, ArrayHash $values): void
    {
        $paymentIds = $this->paymentIds();
        if ($paymentIds === []) {
            $form->addError('Nebyly vybrány žádné platby.');

            return;
        }

        $types = $this->selectedTypes($values->categories);
        if ($types === []) {
            $form->addError('Vyberte alespoň jednu kategorii kontaktů.');

            return;
        }

        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($paymentIds as $paymentId) {
            $payment = $this->paymentService->findPayment($paymentId);
            if ($payment === null || $payment->getGroupId() !== $this->groupId || $payment->isClosed() || $payment->getPersonId() === null) {
                ++$skippedCount;

                continue;
            }

            $recipientsFromSkautis = $this->recipientsFromSkautis($payment->getPersonId(), $types);
            if ($recipientsFromSkautis === []) {
                ++$skippedCount;

                continue;
            }

            $recipients = $this->updatedRecipients($payment, $recipientsFromSkautis, $values->operation);
            if ($this->recipientValues($payment->getEmailRecipients()) === $this->recipientValues($recipients)) {
                ++$skippedCount;

                continue;
            }

            $this->commandBus->handle(new UpdatePaymentEmailRecipients($paymentId, $recipients));
            ++$updatedCount;
        }

        if ($updatedCount > 0) {
            $this->flashMessage(
                $updatedCount === 1 ? 'E-maily byly upraveny u 1 platby.' : 'E-maily byly upraveny u '.$updatedCount.' plateb.',
                'success',
            );
        }
        if ($skippedCount > 0) {
            $this->flashMessage(
                $skippedCount === 1 ? '1 platba nebyla změněna.' : $skippedCount.' plateb nebylo změněno.',
                'info',
            );
        }

        $this->onSuccess();
        $this->hide();
    }

    /** @return array<string, string> */
    private function categoryOptions(): array
    {
        $options = [];
        foreach (MemberEmailType::cases() as $type) {
            if ($type->isBulkSelectable()) {
                $options[$type->value] = $type->getLabel();
            }
        }

        return $options;
    }

    /** @param string[] $categories
     * @return MemberEmailType[]
     */
    private function selectedTypes(array $categories): array
    {
        return array_values(array_filter(array_map(MemberEmailType::tryFrom(...), $categories)));
    }

    /** @param MemberEmailType[] $types
     * @return EmailAddress[]
     */
    private function recipientsFromSkautis(int $personId, array $types): array
    {
        $recipients = [];
        foreach ($this->queryBus->handle(new MemberEmailsQuery($personId)) as $email) {
            if (! $email instanceof MemberEmail || ! in_array($email->getType(), $types, true)) {
                continue;
            }

            $recipients[] = new EmailAddress($email->getAddress());
        }

        return $recipients;
    }

    /**
     * @param EmailAddress[] $recipientsFromSkautis
     *
     * @return EmailAddress[]
     */
    private function updatedRecipients(Payment $payment, array $recipientsFromSkautis, string $operation): array
    {
        if ($operation === self::OPERATION_ADD) {
            return [...$payment->getEmailRecipients(), ...$recipientsFromSkautis];
        }

        $addressesToRemove = $this->recipientValues($recipientsFromSkautis);

        return array_values(array_filter(
            $payment->getEmailRecipients(),
            static fn (EmailAddress $recipient): bool => ! in_array($recipient->getValue(), $addressesToRemove, true),
        ));
    }

    /** @param EmailAddress[] $recipients
     * @return string[]
     */
    private function recipientValues(array $recipients): array
    {
        return array_map(static fn (EmailAddress $recipient): string => $recipient->getValue(), $recipients);
    }

    /** @return int[] */
    private function paymentIds(): array
    {
        return array_map(
            'intval',
            array_filter(explode(',', $this->paymentIds), 'is_numeric'),
        );
    }
}
