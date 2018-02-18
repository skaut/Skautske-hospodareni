<?php

declare(strict_types=1);

namespace Page;

use Cake\Chronos\Date;

class Cashbook
{

    /** @var \AcceptanceTester */
    private $tester;

    public function __construct(\AcceptanceTester $tester)
    {
        $this->tester = $tester;
    }

    public function fillChitForm(Date $date, string $purpose, string $type, string $category, string $recipient, string $amount): void
    {
        $this->tester->fillField('Datum', $date->format('d.m. Y'));
        $this->tester->fillField('Účel', $purpose);
        $this->tester->selectOption('type', $type);
        $this->tester->selectOption('category', $category);
        $this->tester->fillField('Komu/Od', $recipient);
        $this->tester->fillField('price', $amount);
    }

    public function seeBalance(string $balance): void
    {
        $this->tester->see($balance, '.ui--balance');
    }

}
