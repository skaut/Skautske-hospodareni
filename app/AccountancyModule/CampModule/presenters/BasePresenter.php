<?php

declare(strict_types=1);

namespace App\AccountancyModule\CampModule;

use Model\Auth\Resources\Camp as CampResource;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\Event\Camp;
use Model\Event\Exception\CampNotFound;
use Model\Event\ReadModel\Queries\CampQuery;
use Model\Event\SkautisCampId;
use function assert;

class BasePresenter extends \App\AccountancyModule\BasePresenter
{
    protected Camp $event;

    protected function startup() : void
    {
        parent::startup();
        $this->type = ObjectType::CAMP;
        $this->template->setParameters([
            'aid' => $this->aid,
        ]);

        if ($this->aid === null) {
            return;
        }
        $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery(new SkautisCampId($this->aid)));
        try {
            $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));
            assert($cashbook instanceof Cashbook);

            $this->isEditable = $this->authorizator->isAllowed(CampResource::UPDATE_REAL, $this->aid);
            $this->template->setParameters([
                'event' => $this->event = $this->queryBus->handle(new CampQuery(new SkautisCampId($this->aid))),
                'isEditable' => $this->isEditable,
            ]);
        } catch (CampNotFound $exc) {
            $this->template->setParameters(['message' => 'Nemáte oprávnění načíst tábor nebo tábor neexsituje.']);
            $this->forward('accessDenied');
        }
    }

    protected function editableOnly() : void
    {
        if ($this->isEditable) {
            return;
        }

        $this->flashMessage('Akce je uzavřena a nelze ji upravovat.', 'danger');
        if ($this->isAjax()) {
            $this->sendPayload();
        } else {
            $this->redirect('Default:');
        }
    }

    protected function getCampId() : ?int
    {
        return $this->aid;
    }
}
