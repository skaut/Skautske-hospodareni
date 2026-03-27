<?php

declare(strict_types=1);

namespace App\Fixtures\Doctrine;

use App\Model\Invoice\Entity\InvoiceUnitSetting;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class InvoiceUnitSettingFixture extends AbstractFixture implements DependentFixtureInterface
{
    private const UNIT_ID = 25893;
    private const YEAR = 2026;
    private const NAME = '1. skautský oddíl Fixture';
    private const STREET = 'Šikmá 15';
    private const CITY = 'Praha';
    private const ZIPCODE = '11000';
    private const COMPANY_NUMBER = '12345678';
    private const PHONE = '+420 111 222 333';

    public function load(ObjectManager $manager): void
    {
        $existing = $manager->getRepository(InvoiceUnitSetting::class)->findOneBy([
            'unit' => self::UNIT_ID,
            'year' => self::YEAR,
        ]);

        if ($existing !== null) {
            return;
        }

        $setting = new InvoiceUnitSetting(
            self::UNIT_ID,
            self::YEAR,
            self::NAME,
            self::STREET,
            self::CITY,
            self::ZIPCODE,
            self::COMPANY_NUMBER,
            self::PHONE,
        );

        $manager->persist($setting);
        $manager->flush();
    }

    /** @return list<class-string<AbstractFixture>> */
    public function getDependencies(): array
    {
        return [Unit25893PaymentFixture::class];
    }
}
