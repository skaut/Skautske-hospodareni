<?php

declare(strict_types=1);

namespace App\AccountancyModule\CampModule;

use Model\Auth\Resources\Camp as CampResource;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\Event\Camp;
use Model\Event\ReadModel\Queries\CampQuery;
use Model\Event\SkautisCampId;
use Model\EventEntity;
use function assert;

class BasePresenter extends \App\AccountancyModule\BasePresenter
{
    /** @var Camp */
    protected $event;

    /** @var EventEntity */
    protected $eventService;

    protected function startup() : void
    {
        parent::startup();
        $this->eventService = $this->context->getService('campService');
        $this->type         = ObjectType::CAMP;
        $this->template->setParameters([
            'aid' => $this->aid,
        ]);

        if ($this->aid === null) {
            return;
        }
        $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery(new SkautisCampId($this->aid)));
        $cashbook   = $this->queryBus->handle(new CashbookQuery($cashbookId));
        assert($cashbook instanceof Cashbook);

        $this->isEditable = $this->authorizator->isAllowed(CampResource::UPDATE_REAL, $this->aid);
        $this->template->setParameters([
            'event' => $this->event = $this->queryBus->handle(new CampQuery(new SkautisCampId($this->aid))),
            'isEditable' => $this->isEditable,
            'prefix' => $cashbook->getChitNumberPrefix(),
        ]);
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
