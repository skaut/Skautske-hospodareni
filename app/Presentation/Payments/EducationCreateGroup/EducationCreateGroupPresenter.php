<?php

declare(strict_types=1);

namespace App\Presentation\Payments\EducationCreateGroup;

use App\Components\Payment\GroupForm;
use App\Components\Factories\Payment\IGroupFormFactory;
use App\Model\Event\Education;
use App\Model\Event\ReadModel\Queries\EducationListQuery;
use App\Model\Payment\Group\SkautisEntity;
use App\Presentation\Payments\PaymentsBasePresenter as BasePresenter;
use Assert\Assertion;
use Cake\Chronos\ChronosDate;

use function array_key_exists;

final class EducationCreateGroupPresenter extends BasePresenter
{
    private Education $education;

    public function __construct(private IGroupFormFactory $formFactory)
    {
        parent::__construct();
    }

    public function actionDefault(int $educationId): void
    {
        $educations = $this->queryBus->handle(new EducationListQuery(ChronosDate::today()->year)) + $this->queryBus->handle(new EducationListQuery(ChronosDate::today()->year + 1));

        if (! $this->isEditable || ! array_key_exists($educationId, $educations)) {
            $this->flashMessage('Pro tuto vzdělávací akci není možné vytvořit skupinu plateb', 'danger');
            $this->redirect(':Payments:EducationSelectForGroup:');
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
