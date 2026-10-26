<?php

declare(strict_types=1);

namespace App\Presentation\Payments\CampCreateGroup;

use App\Components\Factories\Payment\IGroupFormFactory;
use App\Components\Payment\GroupForm;
use App\Model\Event\Camp;
use App\Model\Payment\Group\SkautisEntity;
use App\Model\Payment\ReadModel\Queries\CampsWithoutGroupQuery;
use App\Presentation\Payments\PaymentsBasePresenter as BasePresenter;
use Cake\Chronos\ChronosDate;
use LogicException;

final class CampCreateGroupPresenter extends BasePresenter
{
    private Camp $camp;

    public function __construct(private IGroupFormFactory $formFactory)
    {
        parent::__construct();
    }

    public function actionDefault(int $campId): void
    {
        $camps = $this->queryBus->handle(new CampsWithoutGroupQuery(ChronosDate::today()->year));

        if (! $this->isEditable || ! isset($camps[$campId])) {
            $this->flashMessage('Pro tento tábor není možné vytvořit skupinu plateb', 'danger');
            $this->redirect(':Payments:CampSelectForGroup:');
        }

        $this->camp = $camps[$campId];
        $this->template->setParameters(['camp' => $this->camp]);

        $form = $this['form'];
        if (! $form instanceof GroupForm) {
            throw new LogicException('Assertion failed.');
        }
        $form->fillName($this->camp->getDisplayName());
    }

    protected function createComponentForm(): GroupForm
    {
        return $this->formFactory->create($this->getCurrentUnitId(), SkautisEntity::fromCampId($this->camp->getId()));
    }
}
