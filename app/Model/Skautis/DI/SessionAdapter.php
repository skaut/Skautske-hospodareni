<?php

declare(strict_types=1);

namespace App\Model\Skautis\DI;

use Nette\Http\Session;
use Nette\Http\SessionSection;
use Skautis\SessionAdapter\AdapterInterface;

/**
 * Adaptér ukládající přihlášení do Skautisu do Nette session.
 *
 * Inlinováno z opuštěného balíčku skautis/nette. Modernizováno na nemagické SessionSection API
 * (set/get místo dříve deprecated magických vlastností).
 */
final class SessionAdapter implements AdapterInterface
{
    /**
     * Název session sekce zachováváme z původního balíčku (třída Skautis\Nette\SessionAdapter),
     * aby přihlášení uživatelů přežilo nasazení této změny.
     */
    private const SECTION_NAME = 'Skautis\Nette\SessionAdapter';

    private SessionSection $sessionSection;

    public function __construct(Session $session)
    {
        $this->sessionSection = $session->getSection(self::SECTION_NAME);
    }

    /**
     * @param string $name
     */
    public function set($name, mixed $object): void
    {
        $this->sessionSection->set($name, $object);
    }

    /** @param string $name */
    public function has($name): bool
    {
        return $this->sessionSection->get($name) !== null;
    }

    /**
     * @param string $name
     */
    public function get($name)
    {
        return $this->sessionSection->get($name);
    }
}
