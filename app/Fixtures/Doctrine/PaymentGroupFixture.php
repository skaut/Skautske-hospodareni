<?php

declare(strict_types=1);

namespace App\Fixtures\Doctrine;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Payment\EmailTemplate;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group;
use App\Model\Payment\Group\PaymentDefaults;
use App\Model\Payment\IUnitResolver;
use App\Model\Payment\Services\IBankAccountAccessChecker;
use App\Model\Payment\Services\IOAuthAccessChecker;
use DateTimeImmutable;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use LogicException;
use Nette\DI\Container;
use Nettrine\Fixtures\ContainerAwareInterface;

use function sprintf;

final class PaymentGroupFixture extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    private const UNIT_ID = 25893;
    private const GROUP_NAME = 'Oddílové příspěvky 2026';
    private const BANK_ACCOUNT_PREFIX = '19';
    private const BANK_ACCOUNT_NUMBER = '17608231';
    private const BANK_ACCOUNT_BANK_CODE = '0100';

    private Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager): void
    {
        $existing = $manager->getRepository(Group::class)->findOneBy([
            'name' => self::GROUP_NAME,
        ]);

        if ($existing instanceof Group) {
            return;
        }

        $bankAccount = $this->findFixtureBankAccount($manager);

        $group = new Group(
            [self::UNIT_ID],
            null,
            self::GROUP_NAME,
            new PaymentDefaults(500.0, null, null, null),
            new DateTimeImmutable(),
            [
                EmailType::PAYMENT_INFO => new EmailTemplate(
                    'Informace o platbě – %groupname%',
                    'Dobrý den,%name%, prosím uhraďte %amount% Kč na účet %account% (VS %vs%) do %maturity%. Děkujeme.',
                ),
            ],
            null,
            $bankAccount,
            $this->container->getByType(IBankAccountAccessChecker::class),
            $this->container->getByType(IOAuthAccessChecker::class),
        );

        $manager->persist($group);
        $manager->flush();
    }

    private function findFixtureBankAccount(ObjectManager $manager): ?BankAccount
    {
        $unitResolver = $this->container->getByType(IUnitResolver::class);
        if (! $unitResolver instanceof IUnitResolver) {
            throw new LogicException('Assertion failed.');
        }
        $officialUnitId = $unitResolver->getOfficialUnitId(self::UNIT_ID);
        $expectedNumber = sprintf('%s-%s/%s', self::BANK_ACCOUNT_PREFIX, self::BANK_ACCOUNT_NUMBER, self::BANK_ACCOUNT_BANK_CODE);

        foreach ($manager->getRepository(BankAccount::class)->findBy(['unitId' => $officialUnitId]) as $account) {
            if (! $account instanceof BankAccount) {
                throw new LogicException('Assertion failed.');
            }
            if ((string) $account->getNumber() === $expectedNumber) {
                return $account;
            }
        }

        return null;
    }

    /** @return list<class-string<AbstractFixture>> */
    public function getDependencies(): array
    {
        return [Unit25893PaymentFixture::class];
    }
}
