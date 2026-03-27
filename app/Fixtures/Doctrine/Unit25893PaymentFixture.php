<?php

declare(strict_types=1);

namespace App\Fixtures\Doctrine;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Common\UnitId;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Payment\IUnitResolver;
use DateTimeImmutable;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Nette\DI\Container;
use Nettrine\Fixtures\ContainerAwareInterface;

use function assert;
use function sprintf;

final class Unit25893PaymentFixture extends AbstractFixture implements ContainerAwareInterface
{
    private const UNIT_ID = 25893;
    private const BANK_ACCOUNT_NAME = 'Fixture GPC ucet';
    private const BANK_ACCOUNT_PREFIX = '19';
    private const BANK_ACCOUNT_NUMBER = '17608231';
    private const BANK_ACCOUNT_BANK_CODE = '0100';
    private const BANK_ACCOUNT_BANK_NAME = 'Komercni banka';
    private const BANK_ACCOUNT_BIC = 'KOMBCZPP';
    private const GOOGLE_EMAIL = 'fixtures-25893@hskauting.local';
    private const GOOGLE_REFRESH_TOKEN = 'fixture-refresh-token-25893';

    private Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager): void
    {
        $unitResolver = $this->getUnitResolver();
        $officialUnitId = $unitResolver->getOfficialUnitId(self::UNIT_ID);
        $bankAccount = $this->findFixtureBankAccount($manager, $officialUnitId);

        if ($bankAccount === null) {
            $bankAccount = new BankAccount(
                self::UNIT_ID,
                self::BANK_ACCOUNT_NAME,
                $this->createFixtureAccountNumber(),
                null,
                new DateTimeImmutable(),
                $unitResolver,
                BankTransactionSource::GPC,
            );

            if ($officialUnitId !== self::UNIT_ID) {
                $bankAccount->allowForSubunits();
            }

            $manager->persist($bankAccount);
        } elseif ($officialUnitId !== self::UNIT_ID && ! $bankAccount->isAllowedForSubunits()) {
            $bankAccount->allowForSubunits();
            $manager->persist($bankAccount);
        }

        if ($this->findFixtureGoogleOAuth($manager) === null) {
            $manager->persist(
                GoogleOAuth::create(
                    new UnitId(self::UNIT_ID),
                    self::GOOGLE_REFRESH_TOKEN,
                    self::GOOGLE_EMAIL,
                ),
            );
        }

        $manager->flush();
    }

    private function findFixtureBankAccount(ObjectManager $manager, int $officialUnitId): ?BankAccount
    {
        $expectedNumber = sprintf(
            '%s-%s/%s',
            self::BANK_ACCOUNT_PREFIX,
            self::BANK_ACCOUNT_NUMBER,
            self::BANK_ACCOUNT_BANK_CODE,
        );

        foreach ($manager->getRepository(BankAccount::class)->findBy(['unitId' => $officialUnitId]) as $account) {
            assert($account instanceof BankAccount);

            if ((string) $account->getNumber() === $expectedNumber) {
                return $account;
            }
        }

        return null;
    }

    private function findFixtureGoogleOAuth(ObjectManager $manager): ?GoogleOAuth
    {
        $oauth = $manager->getRepository(GoogleOAuth::class)->findOneBy([
            'unitId' => new UnitId(self::UNIT_ID),
            'email' => self::GOOGLE_EMAIL,
        ]);

        return $oauth instanceof GoogleOAuth ? $oauth : null;
    }

    private function createFixtureAccountNumber(): AccountNumber
    {
        return new AccountNumber(
            self::BANK_ACCOUNT_PREFIX,
            self::BANK_ACCOUNT_NUMBER,
            self::BANK_ACCOUNT_BANK_CODE,
            self::BANK_ACCOUNT_BANK_NAME,
            null,
            self::BANK_ACCOUNT_BIC,
        );
    }

    private function getUnitResolver(): IUnitResolver
    {
        assert(isset($this->container));

        return $this->container->getByType(IUnitResolver::class);
    }
}
