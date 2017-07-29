<?php

namespace Model\Unit\Repositories;

interface IUnitRepository
{

    public function findByParent(int $parentId);

    public function find(int $id);

}
