<?php

declare(strict_types=1);

namespace Model\Infrastructure\Skautis;

use Skautis\Skautis;
use Skautis\Wsdl\WebServiceInterface;

/**
 * Skautis knihovna při vytvoření instance libovolné webservisy této webservise předává aktuální SOAP options.
 * To ale způsobuje problém např. při přihlášení uživatele - pro nějakou jinou službu se vytvoří instance webservisy
 * ještě před autentizací uživatele a tato služba má po zbytek requestu prázdné ID_Login.
 *
 * Díky této proxy obálce můžeme oddálit vytvoření webservisy až na chvíli, kdy je potřeba,
 * tedy až po přihlášení uživatele.
 */
final class LazyWebService implements WebServiceInterface
{
    private string $webServiceName;

    private Skautis $skautis;

    private ?WebServiceInterface $webService = null;

    public function __construct(string $webServiceName, Skautis $skautis)
    {
        $this->webServiceName = $webServiceName;
        $this->skautis        = $skautis;
    }

    /**
     * @param string  $functionName
     * @param mixed[] $arguments
     *
     * @return mixed
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function call($functionName, array $arguments = [])
    {
        return $this->getWebservice()->call($functionName, $arguments);
    }

    /**
     * @param string  $functionName
     * @param mixed[] $arguments
     *
     * @return mixed
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function __call($functionName, $arguments)
    {
        return $this->getWebservice()->__call($functionName, $arguments);
    }

    /**
     * @param string $eventName
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function subscribe($eventName, callable $callback): void
    {
        $this->getWebservice()->subscribe($eventName, $callback);
    }

    private function getWebservice(): WebServiceInterface
    {
        if ($this->webService === null) {
            $this->webService = $this->skautis->getWebService($this->webServiceName);
        }

        return $this->webService;
    }
}
