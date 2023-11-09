<?php

declare(strict_types=1);

use Codeception\Actor;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends Actor
{
    use _generated\AcceptanceTesterActions;

    private const LOGIN = 'crash01';

    private const PASSWORD = 'chtelbysprachy1';

    public const UNIT_LEADER_ROLE = 'Středisko: vedoucí/admin - 621.66';
    public const UNIT_ID = 27266;

    /**
     * @throws Exception
     */
    public function login(string $role) : void
    {
        $I = $this;

        if ($I->loadSessionSnapshot('login')) {
            $I->amOnPage('/');
            $I->click('Akce');
             return;
        }

        $I->amOnPage('/');
        $I->click('Přihlásit se');
        $I->waitForText('přihlášení');
        $I->fillField('(//input)[9]', self::LOGIN);
        $I->fillField('(//input)[10]', self::PASSWORD);
        $I->click('//button');
//        $I->click('Send anyway'); // bypass ssl redirect
        $I->waitForText('Seznam akcí');

        $roleButtonSelector = "//button[contains(@class, 'ui--current-role')]";

        if ($I->grabTextFrom($roleButtonSelector) !== $role) {
            $I->click($roleButtonSelector);
            $I->click("//a[text()='$role']");
            $I->waitForText($role);
        }

        $I->saveSessionSnapshot('login');
    }

    /**
     * Chrome can't work with popups ¯\_(ツ)_/¯
     */
    public function disablePopups() : void
    {
        $this->executeJS('window.confirm = function(msg){return true;};');
    }
}
