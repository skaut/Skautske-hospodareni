<?php

declare(strict_types=1);

namespace App\AccountancyModule\EducationModule;

use Model\Auth\Resources\Event as ResourceEvent;
use Model\Cashbook\ObjectType;
use Model\Event\Education;
use Model\Event\Exception\EducationNotFound;
use Model\Event\ReadModel\Queries\EducationQuery;
use Model\Event\SkautisEducationId;

use function assert;

class BasePresenter extends \App\AccountancyModule\BasePresenter
{
    protected Education $event;

    protected function startup(): void
    {
        parent::startup();

        $this->type = ObjectType::EDUCATION;
        $this->template->setParameters([
            'aid' => $this->aid,
        ]);

        //pokud je nastavene ID akce tak zjištuje stav dané akce a kontroluje oprávnění
        if ($this->aid === null) {
            return;
        }

        try {
            $this->event = $this->queryBus->handle(new EducationQuery(new SkautisEducationId($this->aid)));
            assert($this->event instanceof Education);
        } catch (EducationNotFound) {
            $this->template->setParameters(['message' => 'Nemáte oprávnění načíst vzdělávací akci nebo neexsituje.']);
            $this->forward('Default:accessDenied');
        }

        $this->template->setParameters([
            'event' => $this->event,
            'isEditable' => $this->isEditable = $this->authorizator->isAllowed(ResourceEvent::UPDATE, $this->aid),
        ]);
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
