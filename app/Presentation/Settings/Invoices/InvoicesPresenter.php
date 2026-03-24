<?php

declare(strict_types=1);

namespace App\Presentation\Settings\Invoices;

use App\Model\Invoice\Entity\InvoiceUnitSetting;
use App\Model\Invoice\Manager\InvoiceUnitSettingManager;
use App\Model\Invoice\Repository\InvoiceUnitSettingRepository;
use Component\Forms\BaseForm;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\ArrayHash;
use Throwable;
use Utility\Ares\ViAresParser;

use function assert;
use function preg_replace;
use function trim;

final class InvoicesPresenter extends \App\Presentation\Settings\SettingsBasePresenter
{
    private int $selectedYear;

    private ?InvoiceUnitSetting $invoiceUnitSetting = null;

    public function __construct(
        private readonly InvoiceUnitSettingRepository $invoiceUnitSettings,
        private readonly InvoiceUnitSettingManager $invoiceUnitSettingManager,
    ) {
        parent::__construct();
    }

    public function actionDefault(?int $year = null): void
    {
        $this->selectedYear = $year ?? (int) date('Y');
        $this->invoiceUnitSetting = $this->invoiceUnitSettings->findByUnitAndYear($this->getUnitId(), $this->selectedYear);
        $this->template->setParameters([
            'selectedYear' => $this->selectedYear,
        ]);
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();
        $this->setSettingsTemplateParameters();
    }

    public function createComponentForm(): BaseForm
    {
        $officialUnit = $this->unitService->getOfficialUnit($this->getUnitId());

        $form = new BaseForm();
        $form->addText('unitName', 'Jednotka')
            ->setDisabled()
            ->setDefaultValue($officialUnit->getDisplayName());
        $form->addYearSelect('year', 'Rok')
            ->setRequired('Vyberte rok')
            ->setDefaultValue($this->selectedYear);

        $loadYear = $form->addSubmit('loadYear', 'Načíst rok');
        $loadYear->setValidationScope([$form['year']]);
        $loadYear->onClick[] = [$this, 'handleLoadYear'];

        $form->addText('companyNumber', 'IČO')
            ->addFilter(fn (string $value) => trim((string) preg_replace('/\s+/', '', $value)))
            ->setRequired('IČO musí být vyplněno');

        $getContactInfo = $form->addSubmit('getContactInfo', 'Získat data z registru');
        $getContactInfo->setHtmlAttribute('class', 'btn btn-primary ajax');
        $getContactInfo->setValidationScope([$form['companyNumber']]);
        $getContactInfo->onClick[] = [$this, 'handleGetContactInfo'];

        $form->addText('name', 'Název')
            ->setRequired('Název musí být vyplněn');
        $form->addText('street', 'Ulice')
            ->setRequired('Ulice musí být vyplněna');
        $form->addText('city', 'Město')
            ->setRequired('Město musí být vyplněno');
        $form->addText('zipcode', 'PSČ')
            ->setRequired('PSČ musí být vyplněno');
        $form->addText('phone', 'Kontaktní telefon')
            ->setRequired(false);

        $form->addSubmit('save', 'Uložit nastavení');
        $form->setDefaults($this->buildDefaults());
        $form->onSuccess[] = [$this, 'formSucceeded'];

        return $form;
    }

    public function handleLoadYear(SubmitButton $button): void
    {
        $values = $button->getForm()->getValues();

        $this->redirect('default', ['year' => (int) $values->year, 'unitId' => $this->getUnitId()]);
    }

    public function handleGetContactInfo(SubmitButton $button): void
    {
        $form = $button->getForm();
        assert($form instanceof BaseForm);

        $values = $form->getValues();
        $selectedYear = $this->resolveSelectedYear($form, $values);

        try {
            $companyInfo = (new ViAresParser())->getAres($values->companyNumber);
        } catch (Throwable) {
            $this->flashMessage('Nepodařilo se načíst údaje z ARES.', 'danger');
            $this->redirect('default', ['year' => $selectedYear, 'unitId' => $this->getUnitId()]);
        }

        if ($companyInfo->isEmpty()) {
            $this->flashMessage('V ARES nebyly nalezeny údaje pro zadané IČO.', 'warning');
            $this->redirect('default', ['year' => $selectedYear, 'unitId' => $this->getUnitId()]);
        }

        $companyInfoArray = $companyInfo->toArray();

        $form->setValues([
            'year' => $selectedYear,
            'companyNumber' => $companyInfo->getCompanyName() ?? $values->companyNumber,
            'name' => $companyInfo->getName(),
            'street' => $companyInfoArray['address'] ?? $companyInfo->getStreet(),
            'city' => $companyInfo->getCity(),
            'zipcode' => $companyInfo->getZipCode(),
        ]);

        $this->flashMessage('Údaje byly načteny z ARES.', 'success');

        if ($this->isAjax()) {
            $this->redrawControl('form');
        } else {
            $this->redirect('default', ['year' => $selectedYear, 'unitId' => $this->getUnitId()]);
        }
    }

    public function formSucceeded(BaseForm $form, ArrayHash $values): void
    {
        if ($form->isSubmitted() !== $form['save']) {
            return;
        }

        $setting = $this->invoiceUnitSettings->findByUnitAndYear($this->getUnitId(), (int) $values->year);

        if ($setting instanceof InvoiceUnitSetting) {
            $setting->updateFromForm($values);
        } else {
            $setting = InvoiceUnitSetting::fromForm($this->getUnitId(), $values);
        }

        $this->invoiceUnitSettingManager->save($setting);
        $this->flashMessage('Roční nastavení fakturace bylo uloženo.', 'success');
        $this->redirect('default', ['year' => (int) $values->year, 'unitId' => $this->getUnitId()]);
    }

    /** @return array<string, mixed> */
    private function buildDefaults(): array
    {
        if ($this->invoiceUnitSetting instanceof InvoiceUnitSetting) {
            return $this->invoiceUnitSetting->toFormValues();
        }

        $officialUnit = $this->unitService->getOfficialUnit($this->getUnitId());

        return InvoiceUnitSetting::fromOfficialUnit($officialUnit, $this->selectedYear)->toFormValues();
    }

    private function resolveSelectedYear(BaseForm $form, ArrayHash $values): int
    {
        if (isset($values->year)) {
            return (int) $values->year;
        }

        $year = $form['year'];
        assert($year instanceof SelectBox);

        $selectedYear = $year->getValue();

        return $selectedYear === null ? $this->selectedYear : (int) $selectedYear;
    }
}
