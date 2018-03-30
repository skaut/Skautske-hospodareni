<?php

namespace Model\Skautis;

use eGen\MessageBus\Bus\CommandBus;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Commands\Cashbook\CreateCashbook;
use Model\Cashbook\ObjectType;
use Model\Payment\IUnitResolver;

class Mapper
{

    /** @var array */
    private $skautisIds = [];

    /** @var array */
    private $localIds = [];

    /** @var ObjectTable */
    private $table;

    /** @var IUnitResolver */
    private $unitResolver;

    /** @var CommandBus */
    private $commandBus;

    public const UNIT = 'unit';
    public const EVENT = 'general';
    public const CAMP = 'camp';

    public function __construct(ObjectTable $table, CommandBus $commandBus, IUnitResolver $unitResolver)
    {
        $this->table = $table;
        $this->commandBus = $commandBus;
        $this->unitResolver = $unitResolver;
    }

    /**
     * Returns ID representing unit/event in Skautis
     */
    public function getSkautisId(int $localId, string $type): ?int
    {
        $key = $type . $localId;

        if ( ! isset($this->skautisIds[$key])) {
            $skautisId = $this->table->getSkautisId($localId, $type);
            $this->cache($skautisId, $localId, $type);
        }

        return $this->skautisIds[$key];
    }

    /**
     * Returns ID representing unit/event in hskauting
     */
    public function getLocalId(int $skautisId, string $type): int
    {
        $key = $type . $skautisId;

        if ( ! isset($this->localIds[$key])) {
            $localId = $this->loadOrCreateLocalId($skautisId, $type);
            $this->cache($skautisId, $localId, $type);
        }

        return $this->localIds[$key];
    }

    private function cache(?int $skautisId, ?int $localId, string $type): void
    {
        $this->skautisIds[$type . $localId] = $skautisId;

        if ($skautisId !== NULL) {
            $this->localIds[$type . $skautisId] = $localId;
        }
    }

    private function loadOrCreateLocalId(int $skautisId, string $type): int
    {
        $localId = $this->table->getLocalId($skautisId, $type);

        if ($localId === NULL) {
            $localId = $this->table->add($skautisId, $type);

            if($type === ObjectType::UNIT) {
                $isOfficialUnit = $this->unitResolver->getOfficialUnitId($skautisId) === $skautisId;
                $type = $isOfficialUnit ? CashbookType::OFFICIAL_UNIT : CashbookType::TROOP;
            }

            $this->commandBus->handle(new CreateCashbook($localId, CashbookType::get($type)));
        }

        return $localId;
    }

}
