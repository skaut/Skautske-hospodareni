<?php

declare(strict_types=1);

namespace App;

use App\Model\BugReport\BugReportScreenshotStorage;
use App\Model\BugReport\BugReportService;
use Component\Forms\BaseForm;
use LogicException;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

use function is_array;
use function strlen;

final class BugReportPresenter extends BasePresenter
{
    private ?string $defaultReportedUrl = null;
    private ?string $reporterEmail = null;

    public function __construct(private BugReportService $bugReportService)
    {
        parent::__construct();
    }

    protected function startup(): void
    {
        parent::startup();

        if ($this->getUser()->isLoggedIn()) {
            return;
        }

        $backlink = $this->storeRequest('+ 3 days');
        if ($this->isAjax()) {
            $this->forward(':Auth:ajax', ['backlink' => $backlink]);
        }

        $this->redirect(':Default:', ['backlink' => $backlink]);
    }

    public function actionDefault(?string $url = null): void
    {
        $this->defaultReportedUrl = $url;
        $this->reporterEmail = $this->bugReportService->getCurrentReporterEmail();
    }

    public function createComponentBugReportForm(): BaseForm
    {
        $form = new BaseForm();
        $form->addTextArea('description', 'Popis chybového chování')
            ->setRequired('Popište prosím chybové chování.')
            ->addRule(Form::MAX_LENGTH, 'Popis může mít nejvýše %d znaků.', 10000)
            ->setHtmlAttribute('rows', 10)
            ->setOption('description', 'Uveďte, co jste dělali, co jste očekávali a co se místo toho stalo.');

        $url = $form->addText('url', 'URL stránky s chybou')
            ->setNullable()
            ->setDefaultValue($this->defaultReportedUrl)
            ->setHtmlAttribute('placeholder', 'https://...');
        $url->addCondition(Form::FILLED)
            ->addRule(Form::URL, 'Zadejte platnou URL adresu.')
            ->addRule(Form::MAX_LENGTH, 'URL může mít nejvýše %d znaků.', 2048);

        if ($this->reporterEmail === null) {
            $form->addEmail('reporterEmail', 'Váš e-mail')
                ->setRequired('Zadejte prosím e-mail, na který vám můžeme odpovědět.')
                ->addRule(Form::MAX_LENGTH, 'E-mail může mít nejvýše %d znaků.', 255)
                ->setOption('description', 'E-mail se použije pro odpověď správců a jako Reply-To v hlášení.');
        }

        $form->addUpload('screenshot', 'Screenshot')
            ->setOption('description', 'Volitelně přiložte screenshot chyby. Soubor musí být obrázek a může mít nejvýše 5 MB.')
            ->setHtmlAttribute('accept', 'image/*')
            ->addRule(Form::MAX_FILE_SIZE, 'Screenshot může mít nejvýše 5 MB.', BugReportScreenshotStorage::MAX_FILE_SIZE);

        $form->addHidden('clientDiagnostics', '{}')
            ->setHtmlAttribute('data-bug-report-diagnostics', 'true');
        $form->addSubmit('send', 'Odeslat hlášení');

        $form->onValidate[] = function (Form $form): void {
            $upload = $form->getValues(\Nette\Utils\ArrayHash::class)->screenshot;
            if (! $upload instanceof FileUpload) {
                throw new LogicException('Assertion failed.');
            }
            if (! $upload->hasFile() || ! $upload->isOk()) {
                return;
            }

            if ($upload->getImageSize() === null) {
                $form->addError('Screenshot musí být platný obrázek.');
            }
        };

        $form->onSuccess[] = function (Form $form): void {
            $values = $form->getValues(\Nette\Utils\ArrayHash::class);
            $reporterEmail = $this->reporterEmail;
            if (isset($values->reporterEmail)) {
                $reporterEmail = (string) $values->reporterEmail;
            }

            $screenshot = $values->screenshot;
            if (! $screenshot instanceof FileUpload) {
                throw new LogicException('Assertion failed.');
            }
            $report = $this->bugReportService->submit(
                (string) $values->description,
                $values->url !== null ? (string) $values->url : null,
                $this->decodeClientDiagnostics((string) $values->clientDiagnostics),
                $reporterEmail,
                $screenshot->hasFile() ? $screenshot : null,
            );

            $message = $report->wasNotificationSent()
                ? 'Hlášení technické chyby bylo uloženo a odesláno správcům.'
                : 'Hlášení technické chyby bylo uloženo. E-mailové upozornění se nepodařilo odeslat.';
            $this->flashMessage($message, $report->wasNotificationSent() ? 'success' : 'warning');
            $this->redirect('this', ['url' => null]);
        };

        return $form;
    }

    /** @return array<string, mixed> */
    private function decodeClientDiagnostics(string $value): array
    {
        if ($value === '' || strlen($value) > 50000) {
            return [];
        }

        try {
            $decoded = Json::decode($value, Json::FORCE_ARRAY);

            return is_array($decoded) ? $decoded : [];
        } catch (JsonException) {
            return [];
        }
    }
}
