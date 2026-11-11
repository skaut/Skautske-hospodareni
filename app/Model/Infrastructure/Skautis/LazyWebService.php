<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Skautis;

use Skaut\Skautis\Skautis;
use Skaut\Skautis\Wsdl\WebServiceInterface;

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
    private ?WebServiceInterface $webService = null;

    public function __construct(private string $webServiceName, private Skautis $skautis)
    {
    }

    /** @param array<int|string, mixed> $arguments */
    public function call(string $functionName, array $arguments = []): mixed
    {
        return $this->getWebservice()->call($functionName, $arguments);
    }

    /** @param array<int|string, mixed> $arguments */
    public function __call(string $functionName, array $arguments): mixed
    {
        return $this->getWebservice()->__call($functionName, $arguments);
    }

    private function getWebservice(): WebServiceInterface
    {
        return $this->webService ??= $this->skautis->getWebService($this->webServiceName);
    }
}
