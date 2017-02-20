<?php

namespace Model\Skautis;

class Mapper
{

    /** @var array */
    private $skautisIds = [];

    /** @var array */
    private $localIds = [];

    /** @var ObjectTable */
    private $table;

    /**
     * UnitMapper constructor.
     * @param ObjectTable $table
     */
    public function __construct(ObjectTable $table)
    {
        $this->table = $table;
    }

    /**
     * Returns ID representing unit/event in Skautis
     * @param int $localId
     * @param string $type
     * @param string $type
     * @return int|null
     */
    public function getSkautisId(int $localId, string $type): ?int
    {
        $key = $type . $localId;
        if (!isset($this->skautisIds[$key])) {
            $skautisId = $this->table->getSkautisId($localId, $type);
            $this->cache($skautisId, $localId, $type);
        }
        $this->skautisIds[$key];
    }

    /**
     * Returns ID representing unit/event in hskauting
     * @param int $skautisId
     * @param string $type
     * @return int
     */
    public function getLocalId(int $skautisId, string $type): int
    {
        $key = $type . $skautisId;
        if (!isset($this->localIds[$key])) {
            $localId = $this->table->getLocalId($skautisId, $type);
            if ($localId === NULL) {
                $localId = $this->table->add($skautisId, $type);
            }
            $this->cache($skautisId, $localId, $type);
        }
        return $this->localIds[$key];
    }

    /**
     * @param int|NULL $skautisId
     * @param int|null $localId
     * @param string $type
     */
    private function cache(?int $skautisId, ?int $localId, string $type): void
    {
        $this->skautisIds[$type . $localId] = $skautisId;
        if ($skautisId) {
            $this->localIds[$type . $skautisId] = $localId;
        }
    }

}
