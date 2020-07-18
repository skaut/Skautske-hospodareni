<?php

declare(strict_types=1);

namespace Model\Skautis\Payment;

use Model\Payment\Repositories\IMemberEmailRepository;
use Nette\Utils\Strings;
use Skautis\Skautis;
use Skautis\Wsdl\PermissionException;
use function assert;
use function is_string;

final class MemberEmailRepository implements IMemberEmailRepository
{
    private Skautis $skautis;

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }

    /**
     * @return array<string, string>
     */
    public function findByMember(int $memberId) : array
    {
        try {
            $contacts = $this->skautis->org->PersonContactAll(['ID_Person' => $memberId]);
        } catch (PermissionException $e) {
            return [];
        }

        $emails = [];

        foreach ($contacts as $c) {
            if (! Strings::startsWith($c->ID_ContactType, 'email')) {
                continue;
            }

            $email = $c->Value;

            assert(is_string($email));

            $emails[$email] = $email . ' – ' . $c->ContactType;
        }

        return $emails;
    }
}
