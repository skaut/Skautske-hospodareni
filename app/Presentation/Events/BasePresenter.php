<?php

declare(strict_types=1);

namespace App\Presentation\Events;

use App\Components\Event\PrivilegesDialog;
use App\Components\Factories\Event\IPrivilegesDialogFactory;
use App\Model\Auth\Resources\Event as ResourceEvent;
use App\Model\Cashbook\ObjectType;
use App\Model\Event\Event;
use App\Model\Event\EventNotFound;
use App\Model\Event\ReadModel\Queries\EventQuery;
use App\Model\Event\SkautisEventId;

use function assert;

class BasePresenter extends \App\BaseSectionPresenter
{
    private IPrivilegesDialogFactory $privilegesDialogFactory;

    public function injectPrivilegesDialogFactory(IPrivilegesDialogFactory $factory): void
    {
        $this->privilegesDialogFactory = $factory;
    }

    protected Event $event;

    protected function startup(): void
    {
        parent::startup();

        $this->type = ObjectType::EVENT;
        $this->template->setParameters([
            'aid' => $this->aid,
        ]);

        // pokud je nastavene ID akce tak zjištuje stav dané akce a kontroluje oprávnění
        if ($this->aid === null) {
            return;
        }

        try {
            $this->event = $this->queryBus->handle(new EventQuery(new SkautisEventId($this->aid)));
            assert($this->event instanceof Event);
        } catch (EventNotFound) {
            $this->template->setParameters(['message' => 'Nemáte oprávnění načíst akci nebo akce neexsituje.']);
            $this->forward('Default:accessDenied');
        }

        $this->template->setParameters([
            'event' => $this->event,
            'isEditable' => $this->isEditable = $this->authorizator->isAllowed(ResourceEvent::UPDATE, $this->aid),
        ]);
    }

    protected function createComponentPrivilegesDialog(): PrivilegesDialog
    {
        if ($this->aid === null) {
            throw new \Nette\Application\BadRequestException('Cannot create privileges dialog without event ID');
        }

        return $this->privilegesDialogFactory->create(
            $this->aid,
            $this->event->getState() === 'draft',
        );
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
}
