<?php

declare(strict_types=1);

namespace Model\Skautis;

use Model\Event\Education;
use Model\Event\Exception\EducationNotFound;
use Model\Event\Repositories\IEducationRepository;
use Model\Event\SkautisEducationId;
use Model\Skautis\Factory\EducationFactory;
use Skautis\Wsdl\PermissionException;
use Skautis\Wsdl\WebServiceInterface;

final class EducationRepository implements IEducationRepository
{
    private WebServiceInterface $webService;

    private EducationFactory $educationFactory;

    public function __construct(WebServiceInterface $webService, EducationFactory $educationFactory)
    {
        $this->webService       = $webService;
        $this->educationFactory = $educationFactory;
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
