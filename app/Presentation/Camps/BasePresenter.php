<?php

declare(strict_types=1);

namespace App\Presentation\Camps;

use App\Components\Camps\PrivilegesDialog;
use App\Components\Factories\Camps\IPrivilegesDialogFactory;
use App\Model\Auth\Resources\Camp as CampResource;
use App\Model\Cashbook\ObjectType;
use App\Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use App\Model\Cashbook\ReadModel\Queries\CashbookQuery;
use App\Model\DTO\Cashbook\Cashbook;
use App\Model\Event\Camp;
use App\Model\Event\Exception\CampNotFound;
use App\Model\Event\ReadModel\Queries\CampQuery;
use App\Model\Event\SkautisCampId;
use LogicException;

class BasePresenter extends \App\BaseSectionPresenter
{
    private IPrivilegesDialogFactory $privilegesDialogFactory;

    public function injectPrivilegesDialogFactory(IPrivilegesDialogFactory $factory): void
    {
        $this->privilegesDialogFactory = $factory;
    }

    protected Camp $event;

    protected function startup(): void
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
        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));
        if (! $cashbook instanceof Cashbook) {
            throw new LogicException('Assertion failed.');
        }
        $this->isEditable = $this->authorizator->isAllowed(CampResource::UPDATE_REAL, $this->aid);

        try {
            $this->template->setParameters([
                'event' => $this->event = $this->queryBus->handle(new CampQuery(new SkautisCampId($this->aid))),
                'isEditable' => $this->isEditable,
            ]);
        } catch (CampNotFound) {
            $this->template->setParameters(['message' => 'Nemáte oprávnění načíst tábor nebo tábor neexsituje.']);
            $this->forward('Default:accessDenied');
        }
    }

    protected function createComponentPrivilegesDialog(): PrivilegesDialog
    {
        if ($this->aid === null) {
            throw new \Nette\Application\BadRequestException('Cannot create privileges dialog without camp ID');
        }

        return $this->privilegesDialogFactory->create($this->aid);
    }

    protected function editableOnly(): void
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

    protected function getCampId(): int
    {
        if ($this->aid === null) {
            throw new LogicException('Camp ID is not set.');
        }

        return $this->aid;
    }
}
