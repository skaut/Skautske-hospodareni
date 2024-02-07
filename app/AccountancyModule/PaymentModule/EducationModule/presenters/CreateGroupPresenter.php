<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\EducationModule;

use App\AccountancyModule\PaymentModule\BasePresenter;
use App\AccountancyModule\PaymentModule\Components\GroupForm;
use App\AccountancyModule\PaymentModule\Factories\IGroupFormFactory;
use Assert\Assertion;
use Cake\Chronos\ChronosDate;
use Model\Event\Education;
use Model\Payment\Group\SkautisEntity;
use Model\Event\ReadModel\Queries\EducationListQuery;

use function array_key_exists;

class CreateGroupPresenter extends BasePresenter
{
    private Education $education;

    public function __construct(private IGroupFormFactory $formFactory)
    {
        parent::__construct();
    }

    public function actionDefault(int $educationId): void
    {
        $educations = $this->queryBus->handle(new EducationListQuery(ChronosDate::today()->year));

        if (! $this->isEditable || ! array_key_exists($educationId, $educations)) {
            $this->flashMessage('Pro tuto vzdělávací akci není možné vytvořit skupinu plateb', 'danger');
            $this->redirect('Education:SelectForGroup:');
        }

        $this->education = $educations[$educationId];
        $this->template->setParameters(['education' => $this->education]);
    }

    protected function createComponentForm(): GroupForm
    {
        Assertion::notNull($this->education);

        $form = $this->formFactory->create(
            $this->getCurrentUnitId(),
            SkautisEntity::fromEducationId($this->education->getId()),
        );

        $form->fillName($this->education->getDisplayName());

        return $form;
    }
}
