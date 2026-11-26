<?php

declare(strict_types=1);

namespace App;

use App\Model\User\Services\LoginTracker;
use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\Application\Responses\CallbackResponse;
use Nette\Http\IRequest;
use Nette\Http\IResponse;

/**
 * Takes the theme the pages show (dark or light) once per login, for the share of dark mode in Admin → Využití.
 * The page sends it in the background, see frontend/ts/themeReport.ts.
 *
 * Deliberately not a BasePresenter: a statistic must not call skautIS, count as activity of the user or run
 * any page. Without a login in the session nothing is stored.
 */
final class ThemeReportPresenter implements IPresenter
{
    public function __construct(private LoginTracker $loginTracker, private IRequest $httpRequest)
    {
    }

    public function run(Request $request): Response
    {
        if ($this->httpRequest->isMethod('POST')) {
            $this->loginTracker->recordTheme($this->httpRequest->getPost('dark') === '1');
        }

        return new CallbackResponse(static function (IRequest $request, IResponse $response): void {
            $response->setCode(IResponse::S204_NoContent);
        });
    }
}
