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
        $form->addCheckbox('extendSkautisLogin', 'Na pozadí prodlužovat přihlášení ke skautISu')
            ->setDefaultValue($this->preferences->shouldExtendSkautisLogin());
        $form->addCheckbox('rememberSkautisRole', 'Pamatovat si zvolenou roli ze skautISu')
            ->setDefaultValue($this->preferences->shouldRememberSkautisRole());
        $form->addSubmit('save', 'Uložit nastavení');
        $form->onSuccess[] = function (Form $form): void {
            $values = $form->getValues(\Nette\Utils\ArrayHash::class);
            $this->preferences->setPreferences(
                (bool) $values->showHelp,
                (bool) $values->extendSkautisLogin,
                (bool) $values->rememberSkautisRole,
                $this->userService->getRoleId(),
            );
            $this->flashMessage('Uživatelské nastavení bylo uloženo.', 'success');
            $this->redirect('this');
        };

        return $form;
    }

    protected function requiresReadableUnit(): bool
    {
        return false;
    }
}
