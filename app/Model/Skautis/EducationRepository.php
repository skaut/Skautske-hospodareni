<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use App\Model\Event\Education;
use App\Model\Event\Exception\EducationNotFound;
use App\Model\Event\Repositories\IEducationRepository;
use App\Model\Event\SkautisEducationId;
use App\Model\Skautis\Factory\EducationFactory;
use Skaut\Skautis\Wsdl\PermissionException;
use Skaut\Skautis\Wsdl\WebServiceInterface;

final class EducationRepository implements IEducationRepository
{
    public function __construct(private WebServiceInterface $webService, private EducationFactory $educationFactory)
    {
    }

    public function find(SkautisEducationId $id): Education
    {
        try {
            $skautisEvent = $this->webService->EventEducationDetail(['ID' => $id->toInt()]);

            return $this->educationFactory->create($skautisEvent);
        } catch (PermissionException $exc) {
            throw new EducationNotFound($exc->getMessage(), $exc->getCode(), $exc);
        }
    }
}
