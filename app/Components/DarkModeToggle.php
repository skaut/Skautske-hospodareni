<?php

declare(strict_types=1);

namespace App\Components;

use App\Model\User\Services\LoginTracker;
use Nette\Application\Attributes\Requires;

/**
 * Switches between the light and the dark theme. The switch itself happens in the browser (frontend/DarkModeToggle.ts);
 * a click of a signed-in user is also sent here, for the share of dark mode in Admin → Využití.
 */
final class DarkModeToggle extends BaseControl
{
    public function __construct(private LoginTracker $loginTracker)
    {
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__.'/templates/DarkModeToggle.latte');
        $this->template->setParameters([
            'reportUrl' => $this->getPresenter()->getUser()->isLoggedIn() ? $this->link('switch!') : null,
        ]);
        $this->template->render();
    }

    #[Requires(methods: 'POST', sameOrigin: true, ajax: true)]
    public function handleSwitch(): void
    {
        $presenter = $this->getPresenter();

        if ($presenter->getUser()->isLoggedIn()) {
            $this->loginTracker->recordTheme($presenter->getHttpRequest()->getPost('theme') === 'dark');
        }

        $presenter->sendPayload();
    }
}
