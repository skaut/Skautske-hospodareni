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

    public const UNIT_LEADER_ROLE = '621.66 - Středisko: vedoucí/admin';

    /**
     * @throws Exception
     */
    public function login(string $role) : void
    {
        $I = $this;

        if($I->loadSessionSnapshot('login')) {
             return;
        }

        $I->amOnPage('/');
        $I->click('Přihlásit se');
        $I->see('přihlášení');
        $I->fillField('(//input)[9]', self::LOGIN);
        $I->fillField('(//input)[10]', self::PASSWORD);
        $I->click('//button');
        $I->waitForText('Seznam akcí');

        $I->click("//select[contains(@class, 'roleSelect')]");
        $I->click("//option[text()='$role']");
        $I->waitForText($role);

        $I->saveSessionSnapshot('login');
    }
}
