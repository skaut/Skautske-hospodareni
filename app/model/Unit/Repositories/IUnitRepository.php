<?php

namespace Model\Unit\Repositories;

use Model\Unit\Unit;

interface IUnitRepository
{

    /**
     * @return Unit[]
     */
    public function findByParent(int $parentId): array;


    /**
     * @return \stdClass|Unit
     */
    public function find(int $id, bool $returnDTO = FALSE);

}
