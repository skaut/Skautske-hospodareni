<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Components\Payment\EmailButton;
use App\Model\Common\Services\CommandBus;
use App\Model\Google\Exception\OAuthNotSet;
use App\Model\Google\InvalidOAuth;
use App\Model\Payment\Commands\Mailing\SendPaymentReminder;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Repositories\IPaymentRepository;
use Codeception\Test\Unit;
use Mockery as m;
use Psr\Log\LoggerInterface;

final class GroupServiceTest extends Unit
{
    public function testReminderSendsCommandsAndLogsSentCount(): void
    {
        $groups = m::mock(IGroupRepository::class);
        $groups->shouldReceive('findByReminder')
            ->once()
            ->andReturn([
                m::mock(['getId' => 10]),
                m::mock(['getId' => 20]),
            ]);

        $payments = m::mock(IPaymentRepository::class);
        $payments->shouldReceive('findByReminder')
            ->once()
            ->with([10, 20])
            ->andReturn([
                m::mock(['getId' => 111]),
                m::mock(['getId' => 222]),
            ]);

        $commandBus = m::mock(CommandBus::class);
        $commandBus->shouldReceive('handle')
            ->once()
            ->withArgs(fn (object $command): bool => $command instanceof SendPaymentReminder && $command->getPaymentId() === 111 && $command->isCli());
        $commandBus->shouldReceive('handle')
            ->once()
            ->withArgs(fn (object $command): bool => $command instanceof SendPaymentReminder && $command->getPaymentId() === 222 && $command->isCli());

        $logger = m::mock(LoggerInterface::class);
        $logger->shouldReceive('info')
            ->once()
            ->with('Sent reminders for 2 payments');

        (new GroupService($groups, $payments, $commandBus, $logger))->reminder();
    }

    public function testReminderLogsKnownExceptionsAndContinues(): void
    {
        $cases = [
            [new OAuthNotSet(), 'OAuth not set'],
            [new InvalidBankAccount(), EmailButton::NO_BANK_ACCOUNT_MESSAGE],
            [new InvalidOAuth(), 'Invalid OAuth'],
            [new EmailTemplateNotSet(), EmailButton::NO_TEMPLATE_ASSIGNED],
            [PaymentHasNoEmails::withName('Test'), 'Payment has no emails'],
        ];

        foreach ($cases as [$exception, $message]) {
            $groups = m::mock(IGroupRepository::class);
            $groups->shouldReceive('findByReminder')
                ->once()
                ->andReturn([m::mock(['getId' => 10])]);

            $payments = m::mock(IPaymentRepository::class);
            $payments->shouldReceive('findByReminder')
                ->once()
                ->with([10])
                ->andReturn([m::mock(['getId' => 111])]);

            $commandBus = m::mock(CommandBus::class);
            $commandBus->shouldReceive('handle')
                ->once()
                ->andThrow($exception);

            $logger = m::mock(LoggerInterface::class);
            $logger->shouldReceive('error')
                ->once()
                ->with($message);
            $logger->shouldReceive('info')
                ->once()
                ->with('Sent reminders for 0 payments');

            (new GroupService($groups, $payments, $commandBus, $logger))->reminder();

            m::close();
        }
    }
}
