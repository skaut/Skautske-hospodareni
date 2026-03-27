<?php

declare(strict_types=1);

namespace App\Model\Invoice\Manager;

use App\Model\Infrastructure\Manager\AbstractManager;
use App\Model\Invoice\Entity\InvoiceUnitSetting;
use App\Model\Logger\LoggerService;
use App\Model\User\UserService;
use Doctrine\ORM\EntityManagerInterface;

class InvoiceUnitSettingManager extends AbstractManager
{
    public function __construct(EntityManagerInterface $entityManager, protected UserService $userService, protected LoggerService $logger)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return InvoiceUnitSetting::class;
    }

    public function save(InvoiceUnitSetting $setting): InvoiceUnitSetting
    {
        $this->em->persist($setting);
        $this->saveEntity($setting);

        return $setting;
    }
}
