<?php


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
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    private const LOGIN = 'crash01';

    private const PASSWORD = 'chtelbysprachy1';

    public function login() : void
    {
        $I = $this;

        if($I->loadSessionSnapshot('login')) {
             return;
        }

        $I->amOnPage('/');
        $I->click('Přihlásit se');
        $I->see('přihlášení');
        $I->fillField('Uživatelské jméno:', self::LOGIN);
        $I->fillField('Heslo:', self::PASSWORD);
        $I->click('Přihlásit');
        $I->waitForText('Seznam akcí');

        $I->saveSessionSnapshot('login');
    }
}
