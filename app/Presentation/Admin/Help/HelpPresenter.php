<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Help;

use App\Model\Help\Entity\PageHelp;
use App\Model\Help\HelpSection;
use App\Model\Help\Manager\PageHelpManager;
use App\Model\Help\PageCatalog;
use App\Presentation\Admin\AdminBasePresenter;
use Component\Forms\BaseForm;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Http\IResponse;

use function count;
use function explode;
use function implode;
use function is_string;
use function preg_split;
use function trim;
use function usort;

use const PREG_SPLIT_NO_EMPTY;

/**
 * Editing of the contextual help shown in the sidebar of a page. The text lives in
 * the database, so giving a page help needs no template change.
 */
final class HelpPresenter extends AdminBasePresenter
{
    private const SECTION_SLOTS = 5;

    private ?string $pageKey = null;

    public function __construct(
        private readonly PageHelpManager $pageHelpManager,
        private readonly PageCatalog $pageCatalog,
    ) {
    }

    public function renderDefault(): void
    {
        $existing = [];

        foreach ($this->pageHelpManager->findAll() as $help) {
            $existing[$help->getPageKey()] = $help;
        }

        $rows = [];

        foreach ($this->pageCatalog->getPages() as $pageKey) {
            $help = $existing[$pageKey] ?? null;
            [$module] = explode(':', $pageKey);

            $rows[] = [
                'pageKey' => $pageKey,
                'module' => $module,
                'sectionCount' => $help?->getSectionCount() ?? 0,
                'hasLead' => $help?->getLead() !== null,
                'updatedAt' => $help?->getUpdatedAt(),
                'updatedByName' => $help?->getUpdatedByName(),
            ];
        }

        // Pages that already have help first, so the list doubles as a coverage overview.
        usort($rows, static function (array $a, array $b): int {
            return [$b['sectionCount'] > 0, $a['pageKey']] <=> [$a['sectionCount'] > 0, $b['pageKey']];
        });

        $this->template->setParameters([
            'rows' => $rows,
            'filledCount' => count($existing),
            'totalCount' => count($this->pageCatalog->getPages()),
        ]);
    }

    public function actionEdit(string $pageKey): void
    {
        if (! $this->pageCatalog->has($pageKey)) {
            throw new BadRequestException('Unknown page '.$pageKey, IResponse::S404_NotFound);
        }

        $this->pageKey = $pageKey;

        $help = $this->pageHelpManager->findForPage($pageKey);

        $this->template->setParameters([
            'pageKey' => $pageKey,
            'help' => $help,
            'sectionSlots' => self::SECTION_SLOTS,
        ]);

        if ($help === null) {
            return;
        }

        $this->fillForm($help);
    }

    private function fillForm(PageHelp $help): void
    {
        $defaults = ['lead' => $help->getLead(), 'sections' => []];

        foreach ($help->getSections() as $index => $section) {
            if ($index >= self::SECTION_SLOTS) {
                break;
            }

            $defaults['sections'][$index] = [
                'heading' => $section->getHeading(),
                'text' => $section->getText(),
                'items' => implode("\n", $section->getItems()),
            ];
        }

        $this['form']->setDefaults($defaults);
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();

        $form->addTextArea('lead', 'Horní proužek pod nadpisem stránky')
            ->setMaxLength(PageHelp::LEAD_MAX_LENGTH)
            ->setHtmlAttribute('rows', 2)
            ->setHtmlAttribute('placeholder', 'Nechte prázdné a použije se text zabudovaný ve stránce.');

        $sections = $form->addContainer('sections');

        for ($index = 0; $index < self::SECTION_SLOTS; ++$index) {
            $section = $sections->addContainer($index);
            $section->addText('heading', 'Nadpis')
                ->setMaxLength(HelpSection::HEADING_MAX_LENGTH)
                ->setHtmlAttribute('placeholder', 'např. Platnost tři roky');
            $section->addTextArea('text', 'Text')
                ->setMaxLength(HelpSection::TEXT_MAX_LENGTH)
                ->setHtmlAttribute('rows', 3);
            $section->addTextArea('items', 'Odrážky, jedna na řádek')
                ->setHtmlAttribute('rows', 3);
        }

        $form->addSubmit('send', 'Uložit nápovědu')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form): void {
            $this->formSubmitted($form);
        };

        return $form;
    }

    private function formSubmitted(Form $form): void
    {
        if ($this->pageKey === null) {
            throw new BadRequestException('Missing page key', IResponse::S400_BadRequest);
        }

        $values = $form->getValues();
        $lead = trim((string) $values->lead);
        $lead = $lead === '' ? null : $lead;
        $sections = [];

        foreach ($values->sections as $section) {
            $heading = trim((string) $section->heading);
            $text = trim((string) $section->text);

            if ($heading === '' && $text === '') {
                continue;
            }

            if ($heading === '' || $text === '') {
                $form->addError('U každé sekce vyplňte nadpis i text, nebo nechte obě pole prázdná.');

                return;
            }

            $items = preg_split('~\R~', trim((string) $section->items), -1, PREG_SPLIT_NO_EMPTY);

            $sections[] = HelpSection::create($heading, $text, $items === false ? [] : $items);
        }

        $this->pageHelpManager->save($this->pageKey, $lead, $sections, $this->getEditorName());

        $this->flashMessage(
            $lead === null && $sections === []
                ? 'Nápověda byla odebrána, na stránce se už nezobrazí.'
                : 'Nápověda byla uložena.',
        );
        $this->redirect('default');
    }

    /** Comes from the session-cached SkautIS user detail, so saving costs no extra call. */
    private function getEditorName(): ?string
    {
        $detail = (array) $this->userService->getUserDetail();

        foreach (['Person', 'DisplayName', 'Name'] as $key) {
            $value = $detail[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
