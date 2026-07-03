<?php

declare(strict_types=1);

namespace App\Model\Skautis\Payment;

use App\Model\DTO\Payment\MemberEmail;
use App\Model\DTO\Payment\MemberEmailType;
use App\Model\Payment\Repositories\IMemberEmailRepository;
use Nette\Utils\Strings;
use Skautis\Skautis;
use Skautis\Wsdl\PermissionException;
use stdClass;

use function array_values;
use function assert;
use function is_string;

final class MemberEmailRepository implements IMemberEmailRepository
{
    public function __construct(private Skautis $skautis)
    {
    }

    /** @return MemberEmail[] */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function findByMember(int $memberId): array
    {
        try {
            $contacts = $this->toArray($this->skautis->org->PersonContactAll(['ID_Person' => $memberId]));
        } catch (PermissionException) {
            return [];
        }

        $emails = [];
        foreach ($contacts as $c) {
            if (! Strings::startsWith($c->ID_ContactType, 'email')) {
                continue;
            }

            $email = $c->Value;
            assert(is_string($email));
            $emails[$email] = new MemberEmail(
                $email,
                ($c->ParentType ?? $c->ContactType).' – '.$email,
                MemberEmailType::fromSkautis(
                    $c->ID_ContactType,
                    $c->ID_ParentType ?? null,
                    $c->ContactType ?? null,
                ),
            );
        }

        try {
            $parents = $this->toArray($this->skautis->org->PersonParentAll(['ID_Person' => $memberId]));
            foreach ($parents as $parent) {
                if (! isset($parent->Email)) {
                    continue;
                }

                $email = $parent->Email;
                assert(is_string($email));

                $emails[$email] = new MemberEmail(
                    $email,
                    $parent->ParentType.' – '.$email,
                    MemberEmailType::fromSkautis(
                        $parent->ID_ContactType ?? null,
                        $parent->ID_ParentType ?? null,
                        $parent->ContactType ?? null,
                    ),
                );
            }
        } catch (PermissionException) {
        }

        return array_values($emails);
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
