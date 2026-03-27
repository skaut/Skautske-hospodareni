<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use App\Model\Event\Camp;
use App\Model\Event\Exception\CampNotFound;
use App\Model\Event\Repositories\ICampRepository;
use App\Model\Event\SkautisCampId;
use App\Model\Skautis\Factory\CampFactory;
use Skautis\Wsdl\PermissionException;
use Skautis\Wsdl\WebServiceInterface;

final class CampRepository implements ICampRepository
{
    public function __construct(private WebServiceInterface $webService, private CampFactory $campFactory)
    {
    }

    public function find(SkautisCampId $id): Camp
    {
        try {
            $skautisEvent = $this->webService->EventCampDetail(['ID' => $id->toInt()]);

            return $this->campFactory->create($skautisEvent);
        } catch (PermissionException $exc) {
            throw new CampNotFound($exc->getMessage(), $exc->getCode(), $exc);
        }
    }
}
