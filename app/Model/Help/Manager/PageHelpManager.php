<?php

declare(strict_types=1);

namespace App\Model\Help\Manager;

use App\Model\Help\Entity\PageHelp;
use App\Model\Help\HelpSection;
use App\Model\Help\Repository\PageHelpRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class PageHelpManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PageHelpRepository $repository,
    ) {
    }

    public function findForPage(string $pageKey): ?PageHelp
    {
        return $this->repository->findByPageKey($pageKey);
    }

    /** @return PageHelp[] */
    public function findAll(): array
    {
        return $this->repository->findAllOrderedByPageKey();
    }

    /**
     * Stores the help for one page. An empty lead together with an empty section list
     * removes the record, which is how an editor turns the panel off again.
     *
     * @param HelpSection[] $sections
     */
    public function save(string $pageKey, ?string $lead, array $sections, ?string $editorName): void
    {
        $help = $this->repository->findByPageKey($pageKey);

        if ($lead === null && $sections === []) {
            if ($help !== null) {
                $this->entityManager->remove($help);
                $this->entityManager->flush();
            }

            return;
        }

        $now = new DateTimeImmutable();

        if ($help === null) {
            $help = new PageHelp($pageKey, $lead, $sections, $now, $editorName);
        } else {
            $help->update($lead, $sections, $now, $editorName);
        }

        $this->entityManager->persist($help);
        $this->entityManager->flush();
    }
}
