<?php

declare(strict_types=1);

namespace App\Presentation\Accessory\Navigation;

use Contributte\MenuControl\IMenuItem;
use Contributte\MenuControl\LinkGenerator\ILinkGenerator;
use Nette\Application\LinkGenerator;
use Nette\Http\Request;

use function array_key_exists;
use function is_array;
use function is_scalar;

final class PreservingLinkGenerator implements ILinkGenerator
{
    public function __construct(
        private LinkGenerator $linkGenerator,
        private Request $httpRequest,
    ) {
    }

    public function link(IMenuItem $item): string
    {
        $action = $item->getActionTarget();
        if ($action !== null) {
            return $this->linkGenerator->link($action, $this->buildActionParameters($item));
        }

        $link = $item->getLink();
        if ($link !== null) {
            return $link;
        }

        return '#';
    }

    public function absoluteLink(IMenuItem $item): string
    {
        $url = $this->httpRequest->getUrl();
        $prefix = $url->getScheme().'://'.$url->getHost();

        if ($url->getPort() !== 80) {
            $prefix .= ':'.$url->getPort();
        }

        return $prefix.$this->link($item);
    }

    /** @return array<string, mixed> */
    private function buildActionParameters(IMenuItem $item): array
    {
        $parameters = $item->getActionParameters();
        $preserveParameters = $item->getDataItem('preserveParameters', []);

        if (! is_array($preserveParameters)) {
            return $parameters;
        }

        foreach ($preserveParameters as $queryParameter => $actionParameter) {
            if (! is_scalar($queryParameter) || ! is_scalar($actionParameter)) {
                continue;
            }

            if (array_key_exists((string) $actionParameter, $parameters)) {
                continue;
            }

            $value = $this->httpRequest->getQuery((string) $queryParameter);
            if ($value === null) {
                continue;
            }

            $parameters[(string) $actionParameter] = $value;
        }

        return $parameters;
    }
}
