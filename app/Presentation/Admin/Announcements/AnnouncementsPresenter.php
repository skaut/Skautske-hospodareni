<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Announcements;

use App\Model\Announcement\Entity\Announcement;
use App\Model\Announcement\Enum\AnnouncementCategoryCode;
use App\Model\Announcement\Manager\AnnouncementManager;
use App\Model\Announcement\Repository\AnnouncementRepository;
use Component\Forms\BaseForm;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;

final class AnnouncementsPresenter extends \App\Presentation\Admin\AdminBasePresenter
{
    private ?Announcement $editedAnnouncement = null;

    public function __construct(
        private readonly AnnouncementRepository $announcementRepository,
        private readonly AnnouncementManager $announcementManager,
    ) {
    }

    public function actionDefault(?int $edit = null): void
    {
        if ($edit === null) {
            return;
        }

        $announcement = $this->announcementRepository->find($edit);
        if (! $announcement instanceof Announcement) {
            throw new BadRequestException('Oznámení nebylo nalezeno.', 404);
        }

        $this->editedAnnouncement = $announcement;
    }

    public function renderDefault(): void
    {
        $this->template->setParameters([
            'adminSection' => 'announcements',
            'announcements' => $this->announcementRepository->findAllForAdministration(),
            'editedAnnouncement' => $this->editedAnnouncement,
            'categoryOptions' => AnnouncementCategoryCode::options(),
            'now' => new DateTimeImmutable(),
        ]);
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

                if ($expiresAt <= new DateTimeImmutable()) {
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
