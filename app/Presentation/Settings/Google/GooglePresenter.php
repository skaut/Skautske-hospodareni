<?php

declare(strict_types=1);

namespace App\Presentation\Settings\Google;

use App\Model\Common\UnitId;
use App\Model\Google\Commands\SaveOAuth;
use App\Model\Google\ReadModel\Queries\OAuthUrlQuery;
use App\Presentation\Settings\SettingsBasePresenter;
use InvalidArgumentException;
use Nette\Utils\Random;
use RuntimeException;

use function hash_equals;
use function in_array;
use function is_string;

final class GooglePresenter extends SettingsBasePresenter
{
    private const STATE_SESSION_SECTION = 'googleOAuth';
    private const STATE_KEY = 'state';

    public function actionOAuth(): void
    {
        $this->assertCanConnectAccount();

        $state = Random::generate(32);
        $this->getSession(self::STATE_SESSION_SECTION)->set(self::STATE_KEY, $state);

        $this->redirectUrl($this->queryBus->handle(new OAuthUrlQuery($state)));
    }

    /**
     * Google returns here with `code` and the `state` sent from actionOAuth(). Without a matching state
     * anyone could send a user a link that connects a foreign Google account to the user's unit.
     * When the user cancels the consent, Google returns `error` instead of `code`.
     */
    public function actionToken(?string $code = null, ?string $state = null): void
    {
        $unitId = $this->assertCanConnectAccount();

        $section = $this->getSession(self::STATE_SESSION_SECTION);
        $expectedState = $section->get(self::STATE_KEY);
        $section->remove(self::STATE_KEY);

        if ($code === null || $state === null || ! is_string($expectedState) || ! hash_equals($expectedState, $state)) {
            $this->flashMessage('Google účet se nepodařilo propojit. Spusťte propojení znovu.', 'danger');
            $this->redirect(':Settings:Mails:default', ['unitId' => $unitId]);
        }

        try {
            $this->commandBus->handle(new SaveOAuth($code, new UnitId($unitId)));
        } catch (InvalidArgumentException) {
            $this->flashMessage('Nepodařilo se propojit Google účet!', 'danger');
            $this->redirect(':Settings:Mails:default', ['unitId' => $unitId]);
        }

        $this->flashMessage('Propojení s Google účtem proběhlo úspěšně!');
        $this->redirect(':Settings:Mails:default', ['unitId' => $unitId]);
    }

    /** The connected account belongs to the unit of the active role, so the role must be allowed to edit it. */
    private function assertCanConnectAccount(): int
    {
        $unitId = ($this->userService->getActualRole() ?? throw new RuntimeException('Není aktivní role.'))->getUnitId();

        if (! in_array($unitId, $this->getEditableUnits(), true)) {
            $this->denyAccess('Nemáte oprávnění propojovat Google účty této jednotky.');
        }

        return $unitId;
    }
}
