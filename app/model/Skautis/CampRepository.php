<?php

declare(strict_types=1);

namespace Model\Skautis;

use Model\Event\Camp;
use Model\Event\Exception\CampNotFound;
use Model\Event\Repositories\ICampRepository;
use Model\Event\SkautisCampId;
use Model\Skautis\Factory\CampFactory;
use Skautis\Wsdl\PermissionException;
use Skautis\Wsdl\WebServiceInterface;

final class CampRepository implements ICampRepository
{
    private WebServiceInterface $webService;

    private CampFactory $campFactory;

    public function __construct(WebServiceInterface $webService, CampFactory $campFactory)
    {
        $this->webService  = $webService;
        $this->campFactory = $campFactory;
    }

    public function find(SkautisCampId $id) : Camp
    {
        try {
            $skautisEvent = $this->webService->EventCampDetail(['ID' => $id->toInt()]);

            return $this->campFactory->create($skautisEvent);
        } catch (PermissionException $exc) {
            throw new CampNotFound($exc->getMessage(), $exc->getCode(), $exc);
        }
    }
}
