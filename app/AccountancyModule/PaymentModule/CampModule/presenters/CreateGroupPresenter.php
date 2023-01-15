<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\CampModule;

use App\AccountancyModule\PaymentModule\BasePresenter;
use App\AccountancyModule\PaymentModule\Components\GroupForm;
use App\AccountancyModule\PaymentModule\Factories\IGroupFormFactory;
use Cake\Chronos\Date;
use Model\Event\Camp;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\ReadModel\Queries\CampsWithoutGroupQuery;

final class CreateGroupPresenter extends BasePresenter
{
    private Camp $camp;

    public function __construct(private IGroupFormFactory $formFactory)
    {
        parent::__construct();
    }

    public function actionDefault(int $campId): void
    {
        $camps = $this->queryBus->handle(new CampsWithoutGroupQuery(Date::today()->year));

        if (! $this->isEditable || ! isset($camps[$campId])) {
            $this->flashMessage('Pro tento tábor není možné vytvořit skupinu plateb', 'danger');
            $this->redirect('SelectForGroup:');
        }

        $this->camp = $camps[$campId];
        $this->template->setParameters(['camp' => $this->camp]);
    }

    protected function createComponentForm(): GroupForm
    {
        $form = $this->formFactory->create($this->getCurrentUnitId(), SkautisEntity::fromCampId($this->camp->getId()));

        $form->fillName($this->camp->getDisplayName());

        return $form;
    }
}
