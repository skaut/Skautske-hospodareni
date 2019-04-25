<?php

declare(strict_types=1);

namespace Model\Skautis\Auth;

use InvalidArgumentException;
use Model\Auth\IAuthorizator;
use Model\Auth\Resources\Camp;
use Model\Auth\Resources\Event;
use Model\Auth\Resources\Unit;
use Skautis\Wsdl\PermissionException;
use Skautis\Wsdl\WebServiceInterface;
use stdClass;
use function count;
use function is_array;

final class SkautisAuthorizator implements IAuthorizator
{
    /** @var WebServiceInterface */
    private $userWebservice;

    private const RESOURCE_CLASS_TO_SKAUTIS_TABLE_MAP = [
        Event::class => Event::TABLE,
        Unit::class => Unit::TABLE,
        Camp::class => Camp::TABLE,
    ];

    public function __construct(WebServiceInterface $userWebservice)
    {
        $this->userWebservice = $userWebservice;
    }

    /**
     * @param mixed[] $action
     */
    public function isAllowed(array $action, ?int $resourceId) : bool
    {
        if (count($action) !== 2 || ! isset(self::RESOURCE_CLASS_TO_SKAUTIS_TABLE_MAP[$action[0]])) {
            throw new InvalidArgumentException('Unknown action');
        }

        $skautisTable = self::RESOURCE_CLASS_TO_SKAUTIS_TABLE_MAP[$action[0]];

        foreach ($this->getAvailableActions($skautisTable, $resourceId) as $skautisAction) {
            if ($skautisAction->ID === $action[1]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return stdClass[]
     */
    private function getAvailableActions(string $skautisTable, ?int $id) : array
    {
        try {
            $result = $this->userWebservice->ActionVerify([
                'ID' => $id,
                'ID_Table' => $skautisTable,
                'ID_Action' => null,
            ]);

            return is_array($result)
                ? $result
                : [];
        } catch (PermissionException $exc) {
            return [];
        }
    }
}
