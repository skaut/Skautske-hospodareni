<?php

namespace Model\Payment\Fio;

use Model\Payment\BankAccount;
use Nette\Caching\Cache;

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


    public function getTransactions(\DateTimeInterface $since, \DateTimeInterface $until, BankAccount $account): array
    {
        $key = sprintf("%s-%s-%s", $account->getId(), $since->format('d-m-Y'), $until->format('d-m-y'));
        return $this->cache->load($key, function(array $dependencies) use($since, $until, $account) {
            $dependencies[Cache::EXPIRATION] = '5 minutes';
            return $this->inner->getTransactions($since, $until, $account);
        });
    }

}
