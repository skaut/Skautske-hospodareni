<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Model\Common\EmailAddress;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Payment\MemberEmail;
use App\Model\DTO\Payment\MemberEmailType;
use App\Model\DTO\Payment\Payment;
use App\Model\Payment\Payment\State;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\MemberEmailsQuery;
use App\Model\Payment\VariableSymbol;
use App\Model\Utils\MoneyFactory;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use Mockery;
use ReflectionMethod;

use function array_map;

final class BulkEmailRecipientsDialogTest extends Unit
{
    public function testUsesOnlySelectedSkautisContactCategories(): void
    {
        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')
            ->once()
            ->withArgs(static fn (object $query): bool => $query instanceof MemberEmailsQuery && $query->getMemberId() === 147006)
            ->andReturn([
                new MemberEmail('main@example.com', 'Hlavní', MemberEmailType::MAIN),
                new MemberEmail('mother@example.com', 'Matka', MemberEmailType::MOTHER),
            ]);

        $dialog = $this->dialog($queryBus);
        $recipients = (new ReflectionMethod($dialog, 'recipientsFromSkautis'))->invoke($dialog, 147006, [MemberEmailType::MOTHER]);

        self::assertSame(['mother@example.com'], $this->recipientValues($recipients));
    }

    public function testRemovingSkautisContactKeepsOtherRecipients(): void
    {
        $dialog = $this->dialog(Mockery::mock(QueryBus::class));
        $recipients = (new ReflectionMethod($dialog, 'updatedRecipients'))->invoke(
            $dialog,
            $this->payment(['manual@example.com', 'mother@example.com']),
            [new EmailAddress('mother@example.com')],
            'remove',
        );

        self::assertSame(['manual@example.com'], $this->recipientValues($recipients));
    }

    private function dialog(QueryBus $queryBus): BulkEmailRecipientsDialog
    {
        return new BulkEmailRecipientsDialog(
            5,
            Mockery::mock(CommandBus::class),
            $queryBus,
            Mockery::mock(PaymentService::class),
        );
    }

    /** @param list<string> $recipients */
    private function payment(array $recipients): Payment
    {
        return new Payment(
            7,
            'Testovací platba',
            MoneyFactory::fromDecimal('500.00'),
            array_map(static fn (string $address): EmailAddress => new EmailAddress($address), $recipients),
            new ChronosDate('2026-10-20'),
            new VariableSymbol('123'),
            null,
            '',
            false,
            State::get(State::PREPARING),
            null,
            null,
            null,
            147006,
            5,
            [],
        );
    }

    /** @param EmailAddress[] $recipients
     * @return string[]
     */
    private function recipientValues(array $recipients): array
    {
        return array_map(static fn (EmailAddress $recipient): string => $recipient->getValue(), $recipients);
    }
}
