<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Announcements;

use App\Components\DataGrid;
use App\Components\Grids\GridFactory;
use App\Model\Announcement\Entity\Announcement;
use App\Model\Announcement\Enum\AnnouncementCategoryCode;
use App\Model\Announcement\Manager\AnnouncementManager;
use App\Model\Announcement\Repository\AnnouncementRepository;
use App\Model\Auth\Resources\Admin;
use Component\Forms\BaseForm;
use Contributte\Datagrid\Column\Action\Confirmation\StringConfirmation;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Utils\Html;

final class AnnouncementsPresenter extends \App\Presentation\Admin\AdminBasePresenter
{
    private ?Announcement $editedAnnouncement = null;

    public function __construct(
        private readonly AnnouncementRepository $announcementRepository,
        private readonly AnnouncementManager $announcementManager,
        private readonly GridFactory $gridFactory,
    ) {
    }

    /** @return string[] */
    protected function getRequiredAdminAccess(): array
    {
        return Admin::ANNOUNCEMENTS_ACCESS;
    }

    public function actionEdit(int $id): void
    {
        $announcement = $this->announcementRepository->find($id);
        if (! $announcement instanceof Announcement) {
            throw new BadRequestException('Oznámení nebylo nalezeno.', 404);
        }

        $this->editedAnnouncement = $announcement;
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        if ($this->getAction() === 'default') {
            return;
        }

        $this->template->navigationBreadcrumbs = [
            ['title' => 'Administrace', 'link' => $this->link(':Admin:Default:default'), 'current' => false],
            ['title' => 'Oznámení', 'link' => $this->link('default'), 'current' => false],
            ['title' => $this->editedAnnouncement === null ? 'Nové oznámení' : 'Upravit oznámení', 'link' => null, 'current' => true],
        ];
    }

    public function renderDefault(): void
    {
        $this->template->adminSection = 'announcements';
    }

    public function renderCreate(): void
    {
        $this->configureAnnouncementFormTemplate();
    }

    public function renderEdit(): void
    {
        $this->configureAnnouncementFormTemplate();
    }

    private function configureAnnouncementFormTemplate(): void
    {
        $this->template->setParameters([
            'adminSection' => 'announcements',
            'editedAnnouncement' => $this->editedAnnouncement,
        ]);
    }

    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->gridFactory->create(__DIR__.'/grid.latte', ['searchLabel' => 'Hledat oznámení']);
        $grid->setPrimaryKey('id');
        $grid->setOuterFilterRendering(true);
        $grid->setCollapsibleOuterFilters(false);
        $grid->setColumnReset(false);
        $grid->setRememberState(false);
        $grid->setDefaultSort(['publishedAt' => DataGrid::SORT_DESC]);

        $now = new DateTimeImmutable();
        $rows = [];
        foreach ($this->announcementRepository->findAllForAdministration() as $announcement) {
            [$status, $statusLabel, $statusClass] = $this->status($announcement, $now);
            $rows[] = [
                'id' => $announcement->getId(),
                'title' => $announcement->getTitle(),
                'message' => $announcement->getMessage(),
                'categoryCode' => $announcement->getCategory()->getCode()->value,
                'category' => $announcement->getCategory()->getCode()->label(),
                'publishedAt' => $announcement->getPublishedAt(),
                'status' => $status,
                'statusLabel' => $statusLabel,
                'statusClass' => $statusClass,
                'hidden' => $announcement->isHidden(),
            ];
        }
        $grid->setDataSource($rows);

        $grid->addColumnText('title', 'Název')
            ->setRenderer(fn (array $row): Html => Html::el('a')
                ->href($this->link('edit', ['id' => $row['id']]))
                ->setText($row['title']))
            ->setSortable();
        $grid->addColumnText('category', 'Kategorie')->setSortable();
        $grid->addColumnDateTime('publishedAt', 'Publikováno')
            ->setFormat('j. n. Y H:i')
            ->setSortable();
        $grid->addColumnText('status', 'Stav')
            ->setRenderer(static fn (array $row): Html => Html::el('span')
                ->class($row['statusClass'])
                ->setText($row['statusLabel']));

        $grid->addFilterText('search', '', ['title', 'message'])
            ->setPlaceholder('Hledat oznámení...');
        $grid->addFilterSelect('categoryCode', 'Kategorie', AnnouncementCategoryCode::options())
            ->setPrompt('Všechny');
        $grid->addFilterSelect('status', 'Stav', [
            'visible' => 'Zobrazeno',
            'hidden' => 'Skryto',
            'expired' => 'Prošlé',
            'scheduled' => 'Naplánováno',
        ])->setPrompt('Všechny');

        $grid->addAction('edit', '', 'edit', ['id' => 'id'])
            ->setIcon('fi fi-rr-pencil')
            ->setTitle('Upravit')
            ->setClass('btn btn-sm btn-light')
            ->setDataAttribute('test', 'admin-announcement-edit');
        $grid->addAction('hide', '', 'toggleHidden!', ['id' => 'id'])
            ->setIcon('fi fi-rr-eye-crossed')
            ->setTitle('Skrýt')
            ->setClass('btn btn-sm btn-outline-secondary')
            ->setRenderCondition(static fn (array $row): bool => ! $row['hidden'])
            ->setDataAttribute('test', 'admin-announcement-toggle');
        $grid->addAction('show', '', 'toggleHidden!', ['id' => 'id'])
            ->setIcon('fi fi-rr-eye')
            ->setTitle('Znovu zobrazit')
            ->setClass('btn btn-sm btn-outline-secondary')
            ->setRenderCondition(static fn (array $row): bool => $row['hidden'])
            ->setDataAttribute('test', 'admin-announcement-toggle');
        $grid->addAction('delete', '', 'deleteAnnouncement!', ['id' => 'id'])
            ->setIcon('fi fi-rr-trash')
            ->setTitle('Trvale smazat')
            ->setClass('btn btn-sm btn-outline-danger')
            ->setConfirmation(new StringConfirmation('Opravdu chcete trvale smazat oznámení „%s“?', 'title'))
            ->setDataAttribute('test', 'admin-announcement-delete');

        return $grid;
    }

    /** @return array{string, string, string} */
    private function status(Announcement $announcement, DateTimeImmutable $now): array
    {
        if ($announcement->isHidden()) {
            return ['hidden', 'Skryto', 'badge text-bg-secondary'];
        }

        if ($announcement->getPublishedAt() > $now) {
            return ['scheduled', 'Naplánováno', 'badge text-bg-info'];
        }

        if ($announcement->getExpiresAt() !== null && $announcement->getExpiresAt() <= $now) {
            return ['expired', 'Prošlé', 'badge text-bg-warning'];
        }

        return ['visible', $announcement->getExpiresAt() === null ? 'Zobrazeno bez expirace' : 'Zobrazeno', 'badge text-bg-success'];
    }

    protected function createComponentAnnouncementForm(): Form
    {
        $form = new BaseForm();
        $form->addText('title', 'Název')
            ->setRequired('Zadejte název oznámení.')
            ->setMaxLength(255)
            ->addRule(Form::MAX_LENGTH, 'Název může mít nejvýše 255 znaků.', 255);
        $form->addTextArea('message', 'Text zprávy')
            ->setRequired('Zadejte text oznámení.')
            ->setMaxLength(Announcement::MAX_MESSAGE_LENGTH)
            ->addRule(Form::MAX_LENGTH, 'Text může mít nejvýše %d znaků.', Announcement::MAX_MESSAGE_LENGTH);
        $categoryControl = $form->addSelect('category', 'Kategorie', AnnouncementCategoryCode::options());
        $categoryControl->setRequired('Vyberte kategorii oznámení.');
        $expiresAtControl = $form->addText('expiresAt', 'Zobrazovat do');
        $expiresAtControl->setHtmlType('datetime-local');
        $form->addSubmit('submit', $this->editedAnnouncement === null ? 'Vytvořit oznámení' : 'Uložit změny');

        if ($this->editedAnnouncement !== null) {
            $form->setDefaults([
                'title' => $this->editedAnnouncement->getTitle(),
                'message' => $this->editedAnnouncement->getMessage(),
                'category' => $this->editedAnnouncement->getCategory()->getCode()->value,
                'expiresAt' => $this->editedAnnouncement->getExpiresAt()?->format('Y-m-d\TH:i'),
            ]);
        }

        $form->onSuccess[] = function (Form $form) use ($categoryControl, $expiresAtControl): void {
            $values = $form->getValues();
            $expiresAtValue = trim((string) $values->expiresAt);
            $expiresAt = null;
            if ($expiresAtValue !== '') {
                try {
                    $expiresAt = new DateTimeImmutable($expiresAtValue);
                } catch (Exception) {
                    $expiresAtControl->addError('Zadejte platné datum a čas.');

                    return;
                }

                $previousExpiry = $this->editedAnnouncement?->getExpiresAt()?->format('Y-m-d\TH:i');
                if ($expiresAt <= new DateTimeImmutable() && $expiresAtValue !== $previousExpiry) {
                    $expiresAtControl->addError('Konec zobrazování musí být v budoucnosti.');

                    return;
                }
            }

            try {
                $category = AnnouncementCategoryCode::tryFrom((string) $values->category);
                if ($category === null) {
                    $categoryControl->addError('Vyberte platnou kategorii.');

                    return;
                }

                if ($this->editedAnnouncement === null) {
                    $this->announcementManager->create((string) $values->title, (string) $values->message, $category, $expiresAt);
                    $this->flashMessage('Oznámení bylo vytvořeno.', 'success');
                } else {
                    $this->announcementManager->update($this->editedAnnouncement, (string) $values->title, (string) $values->message, $category, $expiresAt);
                    $this->flashMessage('Oznámení bylo upraveno.', 'success');
                }

                $this->redirect('default');
            } catch (InvalidArgumentException $exception) {
                $form->addError($exception->getMessage());
            }
        };

        return $form;
    }

    public function handleToggleHidden(int $id): void
    {
        $announcement = $this->announcementRepository->find($id);
        if (! $announcement instanceof Announcement) {
            throw new BadRequestException('Oznámení nebylo nalezeno.', 404);
        }

        $this->announcementManager->setHidden($announcement, ! $announcement->isHidden());
        $this->flashMessage($announcement->isHidden() ? 'Oznámení bylo skryto.' : 'Oznámení bylo znovu zveřejněno.', 'success');
        $this->redirect('default');
    }

    public function handleDeleteAnnouncement(int $id): void
    {
        $announcement = $this->announcementRepository->find($id);
        if (! $announcement instanceof Announcement) {
            throw new BadRequestException('Oznámení nebylo nalezeno.', 404);
        }

        $this->announcementManager->delete($announcement);
        $this->flashMessage('Oznámení bylo trvale smazáno.', 'success');
        $this->redirect('default');
    }
}
