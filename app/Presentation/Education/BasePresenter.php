<?php

declare(strict_types=1);

namespace App\Presentation\Education;

use App\Components\Education\PrivilegesDialog;
use App\Components\Factories\Education\IPrivilegesDialogFactory;
use App\Model\Auth\Resources\Education as ResourceEducation;
use App\Model\Cashbook\ObjectType;
use App\Model\Event\Education;
use App\Model\Event\Exception\EducationNotFound;
use App\Model\Event\ReadModel\Queries\EducationQuery;
use App\Model\Event\SkautisEducationId;
use LogicException;

class BasePresenter extends \App\BaseSectionPresenter
{
    private IPrivilegesDialogFactory $privilegesDialogFactory;

    public function injectPrivilegesDialogFactory(IPrivilegesDialogFactory $factory): void
    {
        $this->privilegesDialogFactory = $factory;
    }

    protected Education $event;

    protected function startup(): void
    {
        parent::startup();

        $this->type = ObjectType::EDUCATION;
        $this->template->setParameters([
            'aid' => $this->aid,
        ]);

        if ($this->aid === null) {
            return;
        }

        try {
            $this->event = $this->queryBus->handle(new EducationQuery(new SkautisEducationId($this->aid)));
            if (! $this->event instanceof Education) {
                throw new LogicException('Assertion failed.');
            }
        } catch (EducationNotFound) {
            $this->template->setParameters(['message' => 'Nemáte oprávnění načíst vzdělávací akci nebo neexsituje.']);
            $this->forward('Default:accessDenied');
        }

        $this->template->setParameters([
            'event' => $this->event,
            'isEditable' => $this->isEditable = $this->authorizator->isAllowed(ResourceEducation::UPDATE, $this->aid),
        ]);
    }

    protected function createComponentPrivilegesDialog(): PrivilegesDialog
    {
        if ($this->aid === null) {
            throw new \Nette\Application\BadRequestException('Cannot create privileges dialog without event ID');
        }

        return $this->privilegesDialogFactory->create(
            $this->aid,
            $this->event->grantId?->toInt(),
        );
    }

    protected function editableOnly(): void
    {
        if ($this->isEditable) {
            return;
        }

        $this->flashMessage('Vzdělávací akce je uzavřena a nelze ji upravovat.', 'danger');
        if ($this->isAjax()) {
            $this->sendPayload();
        } else {
            $this->redirect('Default:');
        }
    }
}
