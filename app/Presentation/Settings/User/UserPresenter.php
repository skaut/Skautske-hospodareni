<?php

declare(strict_types=1);

namespace App\Presentation\Settings\User;

use App\Model\User\UserPreferencesService;
use Component\Forms\BaseForm;
use Nette\Application\UI\Form;

final class UserPresenter extends \App\Presentation\Settings\SettingsBasePresenter
{
    public function __construct(
        private UserPreferencesService $preferences,
    ) {
        parent::__construct();
    }

    public function renderDefault(): void
    {
        $this->setSettingsTemplateParameters();
    }

    public function createComponentPreferencesForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addCheckbox('showHelp', 'Automaticky zobrazovat nápovědu u jednotlivých sekcí')
            ->setDefaultValue($this->preferences->shouldShowHelp());
        $form->addSubmit('save', 'Uložit nastavení');
        $form->onSuccess[] = function (Form $form): void {
            $values = $form->getValues();
            $this->preferences->setShowHelp((bool) $values->showHelp);
            $this->flashMessage('Uživatelské nastavení bylo uloženo.', 'success');
            $this->redirect('this');
        };

        return $form;
    }
}
