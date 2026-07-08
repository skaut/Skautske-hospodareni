<?php

declare(strict_types=1);

namespace App\Model\Payment\Handlers\Payment;

use App\Model\Payment\Commands\Payment\SplitPayment;
use App\Model\Payment\Commands\Payment\SplitPaymentPart;
use App\Model\Payment\InvalidPaymentSplit;
use App\Model\Payment\Payment;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Repositories\IPaymentRepository;
use App\Model\Payment\Services\VariableSymbolCollisionChecker;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

use function array_map;
use function array_sum;
use function round;
use function sprintf;

final class SplitPaymentHandler
{
    public function __construct(
        private IPaymentRepository $payments,
        private IGroupRepository $groups,
        private VariableSymbolCollisionChecker $variableSymbolCollisionChecker,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(SplitPayment $command): void
    {
        $this->entityManager->wrapInTransaction(function () use ($command): void {
            $source = $this->payments->find($command->getPaymentId());
            $this->entityManager->lock($source, LockMode::PESSIMISTIC_WRITE);

            $parts = $command->getParts();
            if ($parts === []) {
                throw new InvalidPaymentSplit('Zadejte alespoň jednu část platby.');
            }

            $sourceAmountInCents = $this->toCents($source->getAmount());
            $splitAmountInCents = array_sum(array_map(
                fn (SplitPaymentPart $part): int => $this->toCents($part->getAmount()),
                $parts,
            ));

            if ($splitAmountInCents > $sourceAmountInCents) {
                throw new InvalidPaymentSplit('Součet dělených částek nesmí být větší než původní částka.');
            }

            $group = $this->groups->find($source->getGroupId());
            $sourceVariableSymbol = $source->getVariableSymbol();
            $remainingSourceAmountInCents = $sourceAmountInCents - $splitAmountInCents;
            $usedVariableSymbols = [];

            foreach ($parts as $part) {
                if ($part->getAmount() <= 0) {
                    throw new InvalidPaymentSplit('Každá dělená částka musí být větší než 0.');
                }

                $variableSymbol = $part->getVariableSymbol();
                $variableSymbolValue = (string) $variableSymbol;
                $partAmountInCents = $this->toCents($part->getAmount());

                if (
                    $sourceVariableSymbol !== null
                    && $sourceVariableSymbol->toInt() === $variableSymbol->toInt()
                    && $remainingSourceAmountInCents === $partAmountInCents
                ) {
                    throw new InvalidPaymentSplit('Stejný variabilní symbol lze při rozdělení použít jen u rozdílných částek.');
                }

                if (isset($usedVariableSymbols[$variableSymbolValue])) {
                    throw new InvalidPaymentSplit('Každá nová platba musí mít jiný variabilní symbol.');
                }

                if ($this->payments->existsPaymentWithVariableSymbolInGroup($source->getGroupId(), $variableSymbol, $source->getId())) {
                    throw new InvalidPaymentSplit(sprintf('Variabilní symbol %s je už použitý v této platební skupině.', $variableSymbolValue));
                }

                $usedVariableSymbols[$variableSymbolValue] = true;
                $this->variableSymbolCollisionChecker->assertUniqueForPayment($group, $source->getId(), $variableSymbol);
            }

            $source->reduceAmountBySplit($splitAmountInCents / 100);

            $splitPayments = array_map(
                fn (SplitPaymentPart $part): Payment => new Payment(
                    $group,
                    $source->getName(),
                    $source->getEmailRecipients(),
                    $part->getAmount(),
                    $source->getDueDate(),
                    $part->getVariableSymbol(),
                    $source->getConstantSymbol(),
                    $source->getPersonId(),
                    $part->getNote() ?? $source->getNote(),
                    $source,
                ),
                $parts,
            );

            $this->payments->saveMany([$source, ...$splitPayments]);
        });
    }

    private function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
