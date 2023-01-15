<?php

declare(strict_types=1);

namespace Model\Skautis\Payment;

use Model\Payment\Repositories\IMemberEmailRepository;
use Nette\Utils\Strings;
use Skautis\Skautis;
use Skautis\Wsdl\PermissionException;
use stdClass;

use function array_merge;
use function assert;
use function is_string;

final class MemberEmailRepository implements IMemberEmailRepository
{
    public function __construct(private Skautis $skautis)
    {
    }

    /** @return array<string, string> */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function findByMember(int $memberId): array
    {
        try {
            $contacts = $this->toArray($this->skautis->org->PersonContactAll(['ID_Person' => $memberId]));
        } catch (PermissionException) {
            return [];
        }

        try {
            $contacts = array_merge($this->toArray($this->skautis->org->PersonContactAllParent(['ID_Person' => $memberId])), $contacts);
        } catch (PermissionException) {
        }

        $emails = [];

        foreach ($contacts as $c) {
            if (! Strings::startsWith($c->ID_ContactType, 'email')) {
                continue;
            }

            $email = $c->Value;

            assert(is_string($email));

            $emails[$email] = $email . ' – ' . ( $c->ParentType ?? $c->ContactType);
        }

        return $emails;
    }

    /**
     * @param stdClass|stdClass[] $response
     *
     * @return stdClass[]
     */
    private function toArray(stdClass|array $response): array
    {
        if ($response instanceof stdClass) {
            return [];
        }

        return $response;
    }
}
