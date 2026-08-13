<?php

declare(strict_types=1);

namespace App\Model\Invoice\Entity;

use App\Model\Common\UnitId;
use App\Model\Google\OAuthId;
use App\Model\Invoice\EmailTemplate;
use App\Model\Invoice\EmailType;
use App\Model\Invoice\Enum\InvoiceSequenceState;
use App\Model\Payment\InvalidVariableSymbol;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Nette\Utils\ArrayHash;
use ReflectionProperty;

/**
 * Číselná řada faktur — formátování čísel a variabilních symbolů, stav řady a šablony e-mailů.
 */
final class InvoiceSequenceTest extends Unit
{
    public function testFromFormMapsValues(): void
    {
        $sequence = InvoiceSequence::fromForm(
            new UnitId(123),
            ArrayHash::from([
                'sequence' => 'FA2026',
                'year' => '2026',
                'description' => 'Hlavní řada',
                'defaultDueDate' => 14,
                'firstNumber' => '00001',
            ]),
            null,
            OAuthId::fromString('11111111-1111-4111-8111-111111111111'),
        );

        self::assertSame(123, $sequence->getUnit());
        self::assertSame('FA2026', $sequence->getSequence());
        self::assertSame(2026, $sequence->getYear());
        self::assertSame('Hlavní řada', $sequence->getDescription());
        self::assertSame('00001', $sequence->getFirstNumber());
        self::assertSame(1, $sequence->getFirstNumberValue());
        self::assertSame(14, $sequence->getDefaultDueDate());
        self::assertNull($sequence->getBankAccount());
        self::assertNotNull($sequence->getOauthId());
    }

    public function testInvoiceNumberIsPaddedToFirstNumberLength(): void
    {
        $sequence = $this->createSequence('FA', firstNumber: '00001');

        self::assertSame(5, $sequence->getNumberLength());
        self::assertSame('FA00042', $sequence->formatInvoiceNumber(42));
        self::assertSame('FA12345', $sequence->formatInvoiceNumber(12345));

        $shortSequence = $this->createSequence('X', firstNumber: '1');
        self::assertSame('X42', $shortSequence->formatInvoiceNumber(42));
    }

    /** @dataProvider provideVariableSymbols */
    public function testVariableSymbolCombinesNumericPrefixWithPaddedNumber(string $sequenceName, string $firstNumber, int $invoiceId, string $expected): void
    {
        self::assertSame(
            $expected,
            (string) $this->createSequence($sequenceName, firstNumber: $firstNumber)->generateVariableSymbol($invoiceId),
        );
    }

    /** @return array<string, array{string, string, int, string}> */
    public function provideVariableSymbols(): array
    {
        return [
            'číselný suffix v prefixu se použije' => ['FA2026', '00001', 42, '202600042'],
            'bez číslic v prefixu zůstane jen číslo' => ['FA', '00001', 42, '42'],
            'prázdný prefix bez odsazení' => ['', '0', 7, '7'],
            'čistě číselný prefix' => ['2026', '001', 7, '2026007'],
        ];
    }

    public function testVariableSymbolForZeroIdIsRejected(): void
    {
        // Krajní případ: ltrim smaže i samotné číslo, fallback vrátí "0" — a to VariableSymbol
        // nepovažuje za platný. V praxi nenastane, ID faktur začínají jedničkou.
        $this->expectException(InvalidVariableSymbol::class);

        $this->createSequence('', firstNumber: '0')->generateVariableSymbol(0);
    }

    public function testNumericPrefixIsTakenFromTheEndOfSequence(): void
    {
        self::assertSame('2026', $this->createSequence('FA2026')->getNumericPrefix());
        self::assertSame('', $this->createSequence('FA')->getNumericPrefix());
        self::assertSame('', $this->createSequence('')->getNumericPrefix());
    }

    /** @dataProvider provideDisplayLabels */
    public function testDisplayLabels(string $sequenceName, ?int $year, string $label, string $contextLabel): void
    {
        $sequence = $this->createSequence($sequenceName);

        if ($year === null) {
            // Konstruktor rok vyžaduje, prázdný ho mají jen historické řady z DB.
            $property = new ReflectionProperty($sequence, 'year');
            $property->setAccessible(true);
            $property->setValue($sequence, null);
        }

        self::assertSame($label, $sequence->getDisplayLabel());
        self::assertSame($contextLabel, $sequence->getDisplayContextLabel());
    }

    /** @return array<string, array{string, int|null, string, string}> */
    public function provideDisplayLabels(): array
    {
        return [
            'prefix a rok' => ['FA', 2026, 'FA/2026', 'řady FA/2026'],
            'bez prefixu' => ['  ', 2026, 'rok 2026 bez prefixu', 'řady pro rok 2026 bez prefixu'],
            'bez roku' => ['FA', null, 'FA/bez roku', 'řady FA/bez roku'],
            'bez prefixu i roku' => ['', null, 'rok bez roku bez prefixu', 'řady pro rok bez roku bez prefixu'],
        ];
    }

    public function testSequenceIsOpenUntilItIsClosed(): void
    {
        $sequence = $this->createSequence('FA');

        self::assertSame(InvoiceSequenceState::OPEN, $sequence->getState());
        self::assertTrue($sequence->isOpen());
        self::assertFalse($sequence->isClosed());

        $sequence->close();

        self::assertTrue($sequence->isClosed());
        self::assertFalse($sequence->isOpen());

        $sequence->reopen();

        self::assertTrue($sequence->isOpen());
    }

    public function testEmailTemplateIsAddedUpdatedAndDisabled(): void
    {
        $sequence = $this->createSequence('FA');
        $type = EmailType::get(EmailType::INVOICE_INFO);

        self::assertNull($sequence->getEmailTemplate($type));
        self::assertFalse($sequence->isEmailEnabled($type));

        $sequence->updateEmail($type, new EmailTemplate('Faktura #1', 'Dobrý den, posíláme fakturu.'));

        $template = $sequence->getEmailTemplate($type);
        self::assertNotNull($template);
        self::assertSame('Faktura #1', $template->getSubject());
        self::assertTrue($sequence->isEmailEnabled($type));

        $sequence->updateEmail($type, new EmailTemplate('Faktura #2', 'Nový text.'));

        self::assertSame('Faktura #2', $sequence->getEmailTemplate($type)?->getSubject());

        $sequence->disableEmail($type);

        self::assertFalse($sequence->isEmailEnabled($type));

        // Vypnutí neexistujícího typu je no-op.
        $sequence->disableEmail(EmailType::get(EmailType::INVOICE_REMINDER));
    }

    public function testPairingSettingsAndLastPairing(): void
    {
        $sequence = $this->createSequence('FA');

        self::assertFalse($sequence->isAutomaticPairingEnabled());
        self::assertNull($sequence->getPairingDaysBack());
        self::assertNull($sequence->getLastPairing());

        $sequence->setAutomaticPairingEnabled(true);
        $sequence->setPairingDaysBack(30);
        $sequence->updateLastPairing(new DateTimeImmutable('2026-07-15 08:00:00'));

        self::assertTrue($sequence->isAutomaticPairingEnabled());
        self::assertSame(30, $sequence->getPairingDaysBack());
        self::assertSame('2026-07-15 08:00:00', $sequence->getLastPairing()?->format('Y-m-d H:i:s'));

        $sequence->invalidateLastPairing();

        self::assertNull($sequence->getLastPairing());
    }

    public function testSettersAndToArray(): void
    {
        $sequence = $this->createSequence('FA');

        $sequence->setSequence('FB');
        $sequence->setUnit(456);
        $sequence->setDefaultDueDate(21);
        $sequence->setPhone('+420123456789');
        $sequence->setOauthId(null);
        $sequence->setSequenceId(9);

        $id = new ReflectionProperty($sequence, 'id');
        $id->setAccessible(true);
        $id->setValue($sequence, 5);

        self::assertSame(9, $sequence->getSequenceId());
        self::assertSame('+420123456789', $sequence->getPhone());

        $array = $sequence->toArray();

        self::assertSame(5, $array['id']);
        self::assertSame('FB', $array['sequence']);
        self::assertSame(456, $array['unit']);
        self::assertSame(21, $array['defaultDueDate']);
        self::assertSame(InvoiceSequenceState::OPEN, $array['state']);
        self::assertNull($array['googleOAuth']);
    }

    private function createSequence(string $sequenceName, int $year = 2026, string $firstNumber = '00001'): InvoiceSequence
    {
        return new InvoiceSequence(123, $sequenceName, $year, 'Popis', null, null, 14, $firstNumber);
    }
}
