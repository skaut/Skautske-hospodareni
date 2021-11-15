<?php

declare(strict_types=1);

namespace Model\Common\Services;

interface QueryBus
{
    /**
     * @return mixed
     */
    public function handle(object $query);
}
