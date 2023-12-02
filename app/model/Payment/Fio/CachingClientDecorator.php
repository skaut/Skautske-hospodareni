<?php

declare(strict_types=1);

namespace Model\Payment\Fio;

use Cake\Chronos\ChronosDate;
use Model\Payment\BankAccount;
use Nette\Caching\Cache;

use function sprintf;

class CachingClientDecorator implements IFioClient
{
    public function __construct(private IFioClient $inner, private Cache $cache)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getTransactions(ChronosDate $since, ChronosDate $until, BankAccount $account): array
    {
        $key = sprintf('%s-%s-%s', $account->getId(), $since->format('d-m-Y'), $until->format('d-m-y'));

        return $this->cache->load(
            $key,
            function (array|null $dependencies = null) use ($since, $until, $account) {
                $dependencies              ??= [];
                $dependencies[Cache::Expire] = '5 minutes';
                $dependencies[Cache::Tags]   = ['fio/' . $account->getId()];

                return $this->inner->getTransactions($since, $until, $account);
            },
        );
    }
}
