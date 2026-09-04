<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Help;

use App\Model\Help\Entity\PageHelp;
use App\Model\Help\HelpSection;
use App\Model\Help\Manager\PageHelpManager;
use App\Model\Help\PageCatalog;
use App\Model\PageView\ReadModel\Queries\PageViewSummaryQuery;
use App\Presentation\Admin\AdminBasePresenter;
use Component\Forms\BaseForm;
use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextInput;
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

    /** Long enough to cover a whole season of the year, short enough to describe today. */
    private const VIEW_WINDOW_DAYS = 90;

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

        $today = new DateTimeImmutable('today');
        $pageViews = $this->queryBus->handle(
            new PageViewSummaryQuery($today->modify('-'.self::VIEW_WINDOW_DAYS.' days'), $today),
        );

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
                'views' => $pageViews->getViews($pageKey),
            ];
        }

        // Pages that already have help first, so the list doubles as a coverage
        // overview; the most used pages lead within each group, which is the order
        // in which writing the missing texts pays off.
        usort($rows, static function (array $a, array $b): int {
            return [$b['sectionCount'] > 0, $b['views'], $a['pageKey']]
                <=> [$a['sectionCount'] > 0, $a['views'], $b['pageKey']];
        });

        $this->template->setParameters([
            'rows' => $rows,
            'filledCount' => count($existing),
            'totalCount' => count($this->pageCatalog->getPages()),
            'viewWindowDays' => self::VIEW_WINDOW_DAYS,
            'viewsSince' => $pageViews->countedSince,
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
        $defaults = [
            'lead' => $help->getLead(),
            'youtubeTitle' => $help->getYoutubeTitle(),
            'youtubeUrl' => $help->getYoutubeUrl(),
            'sections' => [],
        ];

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

        $youtubeTitle = $form->addText('youtubeTitle', 'Název videa YouTube')
            ->setNullable()
            ->setMaxLength(PageHelp::YOUTUBE_TITLE_MAX_LENGTH)
            ->setHtmlAttribute('placeholder', 'např. Jak založit platební skupinu');
        $youtubeUrl = $form->addText('youtubeUrl', 'Odkaz na video YouTube')
            ->setNullable()
            ->setMaxLength(PageHelp::YOUTUBE_URL_MAX_LENGTH)
            ->setHtmlAttribute('placeholder', 'https://www.youtube.com/watch?v=...');
        $youtubeTitle
            ->addConditionOn($youtubeUrl, Form::FILLED)
            ->setRequired('Vyplňte název videa YouTube.');
        $youtubeUrl
            ->addConditionOn($youtubeTitle, Form::FILLED)
            ->setRequired('Vyplňte odkaz na video YouTube.')
            ->addRule(Form::URL, 'Zadejte platnou URL adresu.');

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

        $form->addSubmit('save', 'Uložit')
            ->setHtmlAttribute('class', 'btn btn-primary');
        $form->addSubmit('send', 'Uložit a zavřít')
            ->setHtmlAttribute('class', 'btn btn-outline-secondary');

        $form->onSuccess[] = function (Form $form): void {
            $this->formSubmitted($form);
        };
        $form->onError[] = function (): void {
            if ($this->isAjax()) {
                $this->redrawControl('editorForm');
            }
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
        $youtubeTitle = trim((string) $values->youtubeTitle);
        $youtubeTitle = $youtubeTitle === '' ? null : $youtubeTitle;
        $youtubeUrl = trim((string) $values->youtubeUrl);
        $youtubeUrl = $youtubeUrl === '' ? null : $youtubeUrl;
        $sections = [];

        foreach ($values->sections as $section) {
            $heading = trim((string) $section->heading);
            $text = trim((string) $section->text);

            if ($heading === '' && $text === '') {
                continue;
            }

            if ($heading === '' || $text === '') {
                $form->addError('U každé sekce vyplňte nadpis i text, nebo nechte obě pole prázdná.');

                if ($this->isAjax()) {
                    $this->redrawControl('editorForm');
                }

                return;
            }

            $items = preg_split('~\R~', trim((string) $section->items), -1, PREG_SPLIT_NO_EMPTY);

            $sections[] = HelpSection::create($heading, $text, $items === false ? [] : $items);
        }

        try {
            $this->pageHelpManager->save(
                $this->pageKey,
                $lead,
                $sections,
                $youtubeTitle,
                $youtubeUrl,
                $this->getEditorName(),
            );
        } catch (InvalidArgumentException $exception) {
            $youtubeUrlControl = $form->getComponent('youtubeUrl');
            if (! $youtubeUrlControl instanceof TextInput) {
                throw new LogicException('YouTube URL field is missing.');
            }

            $youtubeUrlControl->addError($exception->getMessage());

            if ($this->isAjax()) {
                $this->redrawControl('editorForm');
            }

            return;
        }

        $this->flashMessage(
            $lead === null && $sections === [] && $youtubeUrl === null
                ? 'Nápověda byla odebrána, na stránce se už nezobrazí.'
                : 'Nápověda byla uložena.',
        );

        if ($form->isSubmitted() === $form['save']) {
            if ($this->isAjax()) {
                $this->template->setParameters([
                    'help' => $this->pageHelpManager->findForPage($this->pageKey),
                ]);
                $this->redrawControl('helpSidebar');
                $this->redrawControl('flash');

                return;
            }

            $this->redirect('edit', ['pageKey' => $this->pageKey]);
        }

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
