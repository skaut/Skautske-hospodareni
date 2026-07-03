<?php

declare(strict_types=1);

namespace App\Presentation\Settings\Invoices;

use App\Context;
use App\Model\Invoice\Entity\InvoiceUnitSetting;
use App\Model\Invoice\Manager\InvoiceUnitSettingManager;
use App\Model\Invoice\Repository\InvoiceUnitSettingRepository;
use App\Presentation\InvoiceAccess\InvoiceAccessGuard;
use Component\Forms\BaseForm;
use InvalidArgumentException;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\FileResponse;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\FileUpload;
use Nette\Utils\ArrayHash;
use Throwable;
use Utility\Ares\ViAresParser;

use function dirname;
use function file_exists;
use function is_dir;
use function mkdir;
use function pathinfo;
use function preg_replace;
use function sprintf;
use function strtolower;
use function trim;
use function unlink;

final class InvoicesPresenter extends \App\Presentation\Settings\SettingsBasePresenter
{
    use InvoiceAccessGuard;

    private const IMAGE_STAMP = 'stamp';
    private const IMAGE_LOGO = 'logo';

    private int $selectedYear;

    private ?InvoiceUnitSetting $invoiceUnitSetting = null;

    public function __construct(
        private readonly InvoiceUnitSettingRepository $invoiceUnitSettings,
        private readonly InvoiceUnitSettingManager $invoiceUnitSettingManager,
        private readonly Context $context,
    ) {
        parent::__construct();
    }

    public function actionDefault(?int $year = null): void
    {
        $this->selectedYear = $year ?? (int) date('Y');
        $this->invoiceUnitSetting = $this->invoiceUnitSettings->findByUnitAndYear($this->getUnitId(), $this->selectedYear);
        $hasStampImage = $this->settingImageExists(self::IMAGE_STAMP);
        $hasLogoImage = $this->settingImageExists(self::IMAGE_LOGO);

        $this->template->setParameters([
            'selectedYear' => $this->selectedYear,
            'hasStampImage' => $hasStampImage,
            'stampImageUrl' => $hasStampImage
                ? $this->link('stampImage!', ['year' => $this->selectedYear, 'unitId' => $this->getUnitId()])
                : null,
            'hasLogoImage' => $hasLogoImage,
            'logoImageUrl' => $hasLogoImage
                ? $this->link('logoImage!', ['year' => $this->selectedYear, 'unitId' => $this->getUnitId()])
                : null,
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

        $form->addUpload('logoImage', 'Logo')
            ->setHtmlAttribute('class', 'form-control')
            ->setOption('description', 'PNG nebo JPEG, maximálně 2 MB. Doporučený poměr je široké logo, přibližně 500 x 140 px.')
            ->setRequired(false)
            ->addRule(BaseForm::MIME_TYPE, 'Logo musí být obrázek ve formátu PNG nebo JPEG.', ['image/png', 'image/jpeg'])
            ->addRule(BaseForm::MAX_FILE_SIZE, 'Maximální povolená velikost souboru je 2 MB', 2 * 1024 * 1024);

        $form->addUpload('stampImage', 'Razítko s podpisem')
            ->setHtmlAttribute('class', 'form-control')
            ->setOption('description', 'PNG nebo JPEG, maximálně 2 MB. Doporučený poměr je široký podpis, přibližně 600 x 220 px.')
            ->setRequired(false)
            ->addRule(BaseForm::MIME_TYPE, 'Razítko musí být obrázek ve formátu PNG nebo JPEG.', ['image/png', 'image/jpeg'])
            ->addRule(BaseForm::MAX_FILE_SIZE, 'Maximální povolená velikost souboru je 2 MB', 2 * 1024 * 1024);

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
        if (! $form instanceof BaseForm) {
            throw new InvalidArgumentException('Očekáván formulář nastavení faktur.');
        }

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

        $logoUpload = $values->logoImage ?? null;
        if ($logoUpload instanceof FileUpload && $logoUpload->isOk()) {
            $this->replaceImage($setting, $logoUpload, self::IMAGE_LOGO);
        }

        $stampUpload = $values->stampImage ?? null;
        if ($stampUpload instanceof FileUpload && $stampUpload->isOk()) {
            $this->replaceImage($setting, $stampUpload, self::IMAGE_STAMP);
        }

        $this->invoiceUnitSettingManager->save($setting);
        $this->flashMessage('Roční nastavení fakturace bylo uloženo.', 'success');
        $this->redirect('default', ['year' => (int) $values->year, 'unitId' => $this->getUnitId()]);
    }

    public function handleRemoveStamp(int $year): void
    {
        $setting = $this->invoiceUnitSettings->findByUnitAndYear($this->getUnitId(), $year);

        if (! $setting instanceof InvoiceUnitSetting) {
            $this->redirect('default', ['year' => $year, 'unitId' => $this->getUnitId()]);
        }

        $this->deleteImage($setting, self::IMAGE_STAMP);
        $this->invoiceUnitSettingManager->save($setting);
        $this->flashMessage('Razítko s podpisem bylo smazáno.', 'success');
        $this->redirect('default', ['year' => $year, 'unitId' => $this->getUnitId()]);
    }

    public function handleStampImage(int $year): void
    {
        $this->sendSettingImage($year, self::IMAGE_STAMP, 'Razítko nebylo nalezeno.');
    }

    public function handleRemoveLogo(int $year): void
    {
        $setting = $this->invoiceUnitSettings->findByUnitAndYear($this->getUnitId(), $year);

        if (! $setting instanceof InvoiceUnitSetting) {
            $this->redirect('default', ['year' => $year, 'unitId' => $this->getUnitId()]);
        }

        $this->deleteImage($setting, self::IMAGE_LOGO);
        $this->invoiceUnitSettingManager->save($setting);
        $this->flashMessage('Logo bylo smazáno.', 'success');
        $this->redirect('default', ['year' => $year, 'unitId' => $this->getUnitId()]);
    }

    public function handleLogoImage(int $year): void
    {
        $this->sendSettingImage($year, self::IMAGE_LOGO, 'Logo nebylo nalezeno.');
    }

    private function sendSettingImage(int $year, string $type, string $notFoundMessage): void
    {
        $setting = $this->invoiceUnitSettings->findByUnitAndYear($this->getUnitId(), $year);
        $imagePath = $setting instanceof InvoiceUnitSetting ? $this->getImagePath($setting, $type) : null;

        if ($imagePath === null || ! file_exists($imagePath)) {
            throw new BadRequestException($notFoundMessage, 404);
        }

        $mimeType = match (strtolower((string) pathinfo($imagePath, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };

        $this->sendResponse(new FileResponse($imagePath, $type === self::IMAGE_LOGO ? 'logo' : 'razitko-podpis', $mimeType, false));
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
        if (! $year instanceof SelectBox) {
            throw new InvalidArgumentException('Formulář nastavení faktur neobsahuje výběr roku.');
        }

        $selectedYear = $year->getValue();

        return $selectedYear === null ? $this->selectedYear : (int) $selectedYear;
    }

    private function replaceImage(InvoiceUnitSetting $setting, FileUpload $upload, string $type): void
    {
        $this->deleteImage($setting, $type);
        $targetPath = $this->buildImagePath($setting, $upload, $type);
        $targetDirectory = dirname($targetPath);
        if (! is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0775, true);
        }

        $upload->move($targetPath);
        $this->setImagePath($setting, $type, $targetPath);
    }

    private function buildImagePath(InvoiceUnitSetting $setting, FileUpload $upload, string $type): string
    {
        $extension = $upload->getContentType() === 'image/png' ? 'png' : 'jpg';

        return sprintf(
            '%s/../uploads/invoice-%s/unit-%d-year-%d.%s',
            $this->context->getAppDir(),
            $type === self::IMAGE_LOGO ? 'logos' : 'stamps',
            $setting->getUnit(),
            $setting->getYear(),
            $extension,
        );
    }

    private function deleteImage(InvoiceUnitSetting $setting, string $type): void
    {
        $imagePath = $this->getImagePath($setting, $type);
        if ($imagePath !== null && file_exists($imagePath)) {
            unlink($imagePath);
        }

        $this->setImagePath($setting, $type, null);
    }

    private function settingImageExists(string $type): bool
    {
        if (! $this->invoiceUnitSetting instanceof InvoiceUnitSetting) {
            return false;
        }

        $imagePath = $this->getImagePath($this->invoiceUnitSetting, $type);

        return $imagePath !== null && file_exists($imagePath);
    }

    private function getImagePath(InvoiceUnitSetting $setting, string $type): ?string
    {
        return $type === self::IMAGE_LOGO ? $setting->getLogoImagePath() : $setting->getStampImagePath();
    }

    private function setImagePath(InvoiceUnitSetting $setting, string $type, ?string $path): void
    {
        if ($type === self::IMAGE_LOGO) {
            $setting->setLogoImagePath($path);

            return;
        }

        $setting->setStampImagePath($path);
    }
}
