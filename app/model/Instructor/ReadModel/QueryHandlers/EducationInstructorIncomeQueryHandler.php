<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\EducationInstructorIncomeQuery;
use Model\Cashbook\ReadModel\Queries\EducationInstructorListQuery;
use Model\Common\Services\QueryBus;
use Model\DTO\Instructor\Instructor;

use function assert;

class EducationInstructorIncomeQueryHandler
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function __invoke(EducationInstructorIncomeQuery $query): float
    {
        $instructor = $this->queryBus->handle(new EducationInstructorListQuery($query->getEducationId()));

        $totalInstructorIncome = 0.0;
        foreach ($instructor as $p) {
            assert($p instanceof Instructor);
            $totalInstructorIncome += $p->getPayment();
        }

        return $totalInstructorIncome;
    }
}
