<?php

declare(strict_types=1);

namespace Model\Payment\Fio;

use DateTimeImmutable;
use Model\Payment\BankAccount;
use Nette\Caching\Cache;
use function sprintf;

class CachingClientDecorator implements IFioClient
{
    /** @var IFioClient */
    private $inner;

    /** @var Cache */
    private $cache;


    public function __construct(IFioClient $inner, Cache $cache)
    {
        $this->inner = $inner;
        $this->cache = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function getTransactions(DateTimeImmutable $since, DateTimeImmutable $until, BankAccount $account) : array
    {
        $key = sprintf('%s-%s-%s', $account->getId(), $since->format('d-m-Y'), $until->format('d-m-y'));
        return $this->cache->load(
            $key,
            function (?array $dependencies = null) use ($since, $until, $account) {
                $dependencies                    = $dependencies ?? [];
                $dependencies[Cache::EXPIRATION] = '5 minutes';
                $dependencies[Cache::TAGS]       = ['fio/' . $account->getId()];
                return $this->inner->getTransactions($since, $until, $account);
            }
        );
    }
}
