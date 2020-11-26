<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use App\AccountancyModule\PaymentModule\Components\GroupForm;
use App\AccountancyModule\PaymentModule\Factories\IGroupFormFactory;
use Assert\Assertion;
use Cake\Chronos\Date;
use Model\Event\Education;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\ReadModel\Queries\EducationsWithoutGroupQuery;
use function array_key_exists;

class CreateEducationGroupPresenter extends BasePresenter
{
    private IGroupFormFactory $formFactory;

    private Education $education;

    public function __construct(IGroupFormFactory $formFactory)
    {
        parent::__construct();
        $this->formFactory = $formFactory;
    }

    public function actionDefault(int $educationId) : void
    {
        $educations = $this->queryBus->handle(new EducationsWithoutGroupQuery(Date::today()->year));

        if (! $this->isEditable || ! array_key_exists($educationId, $educations)) {
            $this->flashMessage('Pro tuto vzdělávací akci není možné vytvořit skupinu plateb', 'danger');
            $this->redirect('SelectEducationForGroup:');
        }

        $this->education = $educations[$educationId];
        $this->template->setParameters(['education' => $this->education]);
    }

    protected function createComponentForm() : GroupForm
    {
        Assertion::notNull($this->education);

        $form = $this->formFactory->create(
            $this->getCurrentUnitId(),
            SkautisEntity::fromEducationId($this->education->getId())
        );

        $form->fillName($this->education->getDisplayName());

        return $form;
    }
}
