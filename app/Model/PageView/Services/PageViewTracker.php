<?php

declare(strict_types=1);

namespace App\Model\PageView\Services;

use App\Model\Help\PageCatalog;
use App\Model\PageView\Entity\PageViewDaily;
use App\Model\PageView\Repository\PageViewDailyRepository;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Throwable;

use function str_starts_with;
use function strlen;

/**
 * Counts page views for the usage statistics in the administration.
 *
 * This is the whole measurement the application does about itself: one counter
 * per page and day, written on the server, with nothing stored in the visitor's
 * browser and no third party involved.
 *
 * Nothing here may ever break a request: every entry point swallows its errors
 * and logs them. A statistic is not a reason for someone to lose a page.
 */
final class PageViewTracker
{
    public function __construct(
        private PageViewDailyRepository $repository,
        private LoggerInterface $logger,
    ) {
    }

    public function record(string $pageKey): void
    {
        try {
            if (! $this->isCountablePage($pageKey) || ! $this->repository->isStorageAvailable()) {
                return;
            }

            $this->repository->increment($pageKey, new DateTimeImmutable());
        } catch (Throwable $e) {
            $this->logger->warning('Nepodařilo se zaznamenat zobrazení stránky pro statistiky využití.', ['exception' => $e]);
        }
    }

    /**
     * Keeps the counters comparable with the page list in the administration:
     * what is not a page there must not become a row here either.
     */
    private function isCountablePage(string $pageKey): bool
    {
        if ($pageKey === '' || strlen($pageKey) > PageViewDaily::PAGE_KEY_MAX_LENGTH) {
            return false;
        }

        foreach (PageCatalog::EXCLUDED_PRESENTERS as $excluded) {
            if (str_starts_with($pageKey, $excluded.':')) {
                return false;
            }
        }

        return true;
    }
}
