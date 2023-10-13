<?php

declare(strict_types=1);

namespace Model\Grant\ReadModel\QueryHandlers;

use Model\Grant\Grant;
use Model\Grant\ReadModel\Queries\GrantQuery;
use Model\Skautis\Factory\GrantFactory;
use Skautis\Skautis;

class GrantQueryHandler
{
    public function __construct(private Skautis $skautis, private GrantFactory $grantFactory)
    {
    }

    public function __invoke(GrantQuery $query): Grant
    {
        $grant = $this->skautis->Grants->GrantDetail([
            'ID' => $query->getGrantId(),
        ]);

        return $this->grantFactory->create($grant);
    }
}
