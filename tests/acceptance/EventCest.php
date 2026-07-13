<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use Nette\Utils\DateTime;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class EventCest extends BaseAcceptanceCest
{
    protected AcceptanceTester $I;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->I = $I;
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
    }

    public function createEvent(): void
    {
        $I = $this->I;

        $I->wantTo('Create event');

        $I->click('Akce');
        $I->waitForText('Seznam akcí', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->click('Založit novou akci');
        $I->waitForText('Nová akce', AcceptanceTester::ELEMENT_LOAD_TIMEOUT);
        $I->fillField('Název akce', 'Jaráky');
        $I->fillField('Od', (new DateTime())->format('d.m. Y'));
        $I->fillField('Do', (new DateTime())->format('d.m. Y'));
    }
}
