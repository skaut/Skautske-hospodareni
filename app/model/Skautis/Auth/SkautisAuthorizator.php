<?php

declare(strict_types=1);

namespace Model\Skautis\Auth;

use InvalidArgumentException;
use Model\Auth\IAuthorizator;
use Model\Auth\Resources\Camp;
use Model\Auth\Resources\Education;
use Model\Auth\Resources\Event;
use Model\Auth\Resources\Grant;
use Model\Auth\Resources\Unit;
use Skautis\Wsdl\PermissionException;
use Skautis\Wsdl\WebServiceInterface;
use stdClass;

use function count;
use function is_array;

final class SkautisAuthorizator implements IAuthorizator
{
    private const RESOURCE_CLASS_TO_SKAUTIS_TABLE_MAP = [
        Camp::class => Camp::TABLE,
        Education::class => Education::TABLE,
        Event::class => Event::TABLE,
        Grant::class => Grant::TABLE,
        Unit::class => Unit::TABLE,
    ];

    public function __construct(private WebServiceInterface $userWebservice)
    {
    }

    /** @param mixed[] $action */
    public function isAllowed(array $action, int|null $resourceId): bool
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

    /** @return stdClass[] */
    private function getAvailableActions(string $skautisTable, int|null $id): array
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
        } catch (PermissionException) {
            return [];
        }
    }
}
