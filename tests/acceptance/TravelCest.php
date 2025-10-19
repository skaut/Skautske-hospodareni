<?php

declare(strict_types=1);

namespace acceptance;

use AcceptanceTester;
use Facebook\WebDriver\WebDriverKeys;
use Throwable;

use function json_encode;
use function sprintf;
use function time;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
class TravelCest extends BaseAcceptanceCest
{
    protected AcceptanceTester $I;

    // Test data
    protected string $vehicleType = 'Osobní';
    protected string $licensePlate;
    protected string $harmonizedConsumption = '5.8';
    protected string $division              = '621.66.014 - Frantův oddíl';
    protected string $unitRepresentative    = 'Karel Vedoucí';

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->I = $I;
        $I->login(AcceptanceTester::UNIT_LEADER_ROLE);
        $this->licensePlate = 'RZ-' . time();
    }

    /** @depends createVehicle */
    public function createTravelOrder(AcceptanceTester $I): void
    {
        $name               = 'Porada s vedoucími';
        $unitRepresentative = 'Pavel Zástupce';
        $licensePlate       = 'RZA-' . time();
        $I->wantTo('Create travel order');
        $this->navigateToVehicle($I);
        $this->newVehicle($I, $licensePlate);
        $this->navigationToContract($I);
        $this->newContract($I, $unitRepresentative);
        $this->navigateToTravelOrder($I);
        $this->newTravelOrder($I, $name, $licensePlate);
        $this->navigateToTravelOrder($I);
        $this->deleteTravelOrder($I, $name);
        $this->navigateToVehicle($I);
        $this->deleteVehicle($I, $licensePlate);
        $this->navigationToContract($I);
        $this->deleteContract($I, $unitRepresentative);
    }

    protected function navigateToTravelOrder(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->click('Cesťáky');
        $I->seeInCurrentUrl('/cestaky');
    }

    protected function newTravelOrder(AcceptanceTester $I, string $name, string $licensePlate): void
    {
        $I->click('Založit cestovní příkaz');
        $I->waitForText('Založit cestovní příkaz');

        $I->waitForElementVisible('#frm-form-form-purpose', 10);

        $I->fillField('#frm-form-form-purpose', $name);
        $I->fillField('#frm-form-form-place', 'Praha');
        $I->fillField('#frm-form-form-fellowPassengers', 'Pepa Novák, Alena Malá');
        $I->fillField('#frm-form-form-note', 'Vzít materiál ze skladu');

        $I->selectOption('#frm-form-form-type', ['car']);
        $I->click(['xpath' => '//*[@id="frm-form-form-contract_id"]/option[@value!=""][1]']);

        $I->waitForElementNotVisible('#passengerName', 5);
        $I->waitForElementNotVisible('#passengerContact', 5);
        $I->waitForElementNotVisible('#passengerAddress', 5);

        // (Alternativa bez smlouvy – odkomentuj a vyplň)
        /*
        $I->selectOption('#frm-form-form-contract_id', '');
        $I->executeJS('document.getElementById("frm-form-form-contract_id").dispatchEvent(new Event("change",{bubbles:true}));');
        $I->waitForElementVisible('#passengerName', 5);
        $I->fillField('#frm-form-form-passenger-name',     'Jan Novák');
        $I->fillField('#frm-form-form-passenger-contact',  '777123456');
        $I->fillField('#frm-form-form-passenger-address',  'Ulice 1, Praha');
        */

        // 5) Vozidlo – po výběru "car" jsou pole povinná
        $I->waitForElementVisible('#frm-form-form-vehicle_id', 5);
        $I->selectOption('#frm-form-form-vehicle_id', ['text' => 'Osobní (' . $licensePlate . ')']);
        $I->fillField('#frm-form-form-fuel_price', '38.50');
        $I->fillField('#frm-form-form-amortization', '1.20');

        // 6) Odeslání
        $I->scrollTo('footer');
        $I->waitForElementVisible('[name=send]', 5);
        $I->click('[name=send]');

        // 7) Ověření (uprav dle app – flash zpráva / redirect / nadpis)
        $I->waitForText('Cestovní příkaz byl založen', 10);
        $I->seeInCurrentUrl('/cestaky');
    }

    protected function deleteTravelOrder(AcceptanceTester $I, string $name): void
    {
        $I->click($name);
        $I->waitForText('Cestovní příkaz');
        $I->click('Smazat');
        try {
            $I->acceptPopup();
        } catch (Throwable) {
        }

        $I->waitForText('Cestovní příkaz byl smazán.');
    }

    /** @depends createContract */
    public function createVehicle(AcceptanceTester $I): void
    {
        $I->wantTo('Create vehicle');
        $this->navigateToVehicle($I);
        $this->newVehicle($I, $this->licensePlate);
        $this->navigateToVehicle($I);
        $this->checkVehicle($I, $this->licensePlate);
        $this->navigateToVehicle($I);
        $this->deleteVehicle($I, $this->licensePlate);
    }

    protected function navigateToVehicle(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->click('Cesťáky');
        $I->seeInCurrentUrl('/cestaky');
        $I->click('Vozidla');
        $I->seeInCurrentUrl('/cestaky/vozidla');
    }

    protected function newVehicle(AcceptanceTester $I, string $licensePlate): void
    {
        $I->click('Založit nové vozidlo');
        $I->see('Nové vozidlo');

        $I->fillField(['id' => 'frm-formCreateVehicle-type'], $this->vehicleType);
        $I->fillField(['id' => 'frm-formCreateVehicle-registration'], $licensePlate);
        $I->fillField(['id' => 'frm-formCreateVehicle-consumption'], $this->harmonizedConsumption);
        $I->selectOption(['id' => 'frm-formCreateVehicle-subunitId'], $this->division);
        $I->click('Založit');
        $I->click('Založit nové vozidlo');
        $I->see('Nové vozidlo');

        $I->fillField(['id' => 'frm-formCreateVehicle-type'], $this->vehicleType);
        $I->fillField(['id' => 'frm-formCreateVehicle-registration'], $licensePlate);
        $I->fillField(['id' => 'frm-formCreateVehicle-consumption'], $this->harmonizedConsumption);
        $I->selectOption(['id' => 'frm-formCreateVehicle-subunitId'], $this->division);
        $I->click('Založit');
    }

    protected function checkVehicle(AcceptanceTester $I, string $licensePlate): void
    {
        $I->see('Vozidla');
        $I->see($licensePlate);
        $I->see($this->vehicleType);
        $I->see($this->division);
        $I->click('#frm-grid-grid-filter-filter-search');
        $I->fillField('#frm-grid-grid-filter-filter-search', 'AUV');
        $I->pressKey('#frm-grid-grid-filter-filter-search', [WebDriverKeys::ENTER]);
        $I->wait(3);
        $I->dontSee($licensePlate, '#snippet-grid-grid-table');
        $I->see('Nenalezeny žádné záznamy.', '#snippet-grid-grid-table');
        $I->click('#frm-grid-grid-filter-filter-search');
        $I->fillField('#frm-grid-grid-filter-filter-search', $licensePlate);
        $I->pressKey('#frm-grid-grid-filter-filter-search', [WebDriverKeys::ENTER]);
        $I->waitForText($licensePlate, 10, '#snippet-grid-grid-table');
        $I->wait(3);
        $I->click($licensePlate, '#snippet-grid-grid-table');
        $I->waitForText('Údaje o vozidle');
    }

    protected function deleteVehicle(AcceptanceTester $I, string $licensePlate): void
    {
        $I->click($licensePlate, '#snippet-grid-grid-table');
        $I->waitForText('Údaje o vozidle');
        $I->click('Smazat vozidlo');
        try {
            $I->acceptPopup();
        } catch (Throwable) {
        }

        $I->waitForText('Vozidlo bylo odebráno.');
    }

    public function _createContract(AcceptanceTester $I): void
    {
        $I->wantTo('Create contract');
        $this->navigationToContract($I);
        $this->newContract($I, $this->unitRepresentative);
        $this->navigationToContract($I);
        $this->detailContract($I, $this->unitRepresentative);
        $this->navigationToContract($I);
        $this->deleteContract($I, $this->unitRepresentative);
    }

    protected function navigationToContract(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->click('Cesťáky');
        $I->seeInCurrentUrl('/cestaky');
        $I->click('Smlouvy');
        $I->seeInCurrentUrl('/cestaky/smlouvy');
        $I->waitForText('Smlouvy');
    }

    protected function newContract(AcceptanceTester $I, string $unitRepresentative): void
    {
        $I->click('Založit smlouvu');
        $I->waitForText('Nová smlouva o proplácení cestovních náhrad');
        $I->waitForElementVisible('#frm-formCreateContract', 10);
        $I->fillField('#frm-formCreateContract-passengerName', 'Jan Novák');
        $I->fillField('#frm-formCreateContract-passengerAddress', 'Ulice 1, 100 00 Praha');
        $I->fillField('#frm-formCreateContract-passengerContact', '777123456');
        $I->fillField('#frm-formCreateContract-unitRepresentative', $unitRepresentative);
        $I->fillField('#frm-formCreateContract-start', '18.10.2025');
        $I->click('#frm-formCreateContract-passengerBirthday');
        $I->fillField('#frm-formCreateContract-passengerBirthday', '01.01.1990');
        $I->pressKey('#frm-formCreateContract-passengerBirthday', [WebDriverKeys::TAB]);
        $I->scrollTo('footer');
        $I->click('#frm-formCreateContract [name=send]');
        $I->waitForText('Smlouva byla založena.');
        $I->see($unitRepresentative);
    }

    protected function detailContract(AcceptanceTester $I, string $unitRepresentative): void
    {
        $I->click(sprintf('[data-test="%s"]', $unitRepresentative));
        $I->waitForElementVisible('body', 10);
        $I->see('Smlouva o proplácení cestovních náhrad');

        // --- Vytisknout -> PDF ve stejném tabu ---
        $I->waitForElementVisible('[data-test="contract-print"]', 10);
        $urlBefore = $I->grabFromCurrentUrl();
        $I->click('[data-test="contract-print"]');

        $I->waitForJS('return window.location.href !== ' . json_encode($urlBefore) . ';', 10);
        $I->seeInCurrentUrl('/print');

        $I->moveBack();

        $I->waitForText('Údaje smlouvy', 10);
    }

    protected function deleteContract(AcceptanceTester $I, string $unitRepresentative): void
    {
        $I->click(sprintf('[data-test="%s"]', $unitRepresentative));
        $I->waitForElementVisible('body', 10);
        $I->see('Smlouva o proplácení cestovních náhrad');
        $I->waitForElementVisible('[data-test="contract-delete"]', 10);
        // --- Smazat ---
        $I->click('[data-test="contract-delete"]');
        try {
            $I->acceptPopup();
        } catch (Throwable) {
        }

        $I->waitForText('Smlouva byla smazána', 10);
        $I->seeInCurrentUrl('/smlouvy');
    }
}
