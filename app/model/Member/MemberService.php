<?php

declare(strict_types=1);

namespace Model;

use DateTime;
use Skautis\Wsdl\WebServiceInterface;
use stdClass;
use function asort;

class MemberService
{
    /** @var WebServiceInterface @todo Create anticorruption layer */
    private $organizationWebservice;

    public function __construct(WebServiceInterface $organizationWebservice)
    {
        $this->organizationWebservice = $organizationWebservice;
    }

    /**
     * vytvoří pole jmen s ID pro combobox
     *
     * @param bool $OnlyDirectMember - vybrat pouze z aktuální jednotky?
     *
     * @return string[]
     */
    public function getCombobox(bool $OnlyDirectMember = false, ?int $ageLimit = null) : array
    {
        return $this->getPairs($this->organizationWebservice->PersonAll(['OnlyDirectMember' => $OnlyDirectMember]), $ageLimit);
    }

    /**
     * vrací pole osob ID => jméno
     *
     * @param stdClass[]|stdClass $data - vráceno z PersonAll
     *
     * @return string[]
     */
    private function getPairs($data, ?int $ageLimit = null) : array
    {
        if ($data instanceof stdClass) {
            $data = [$data];
        }
        $res = [];
        $now = new DateTime();
        foreach ($data as $p) {
            if ($ageLimit !== null) {
                if (! isset($p->Birthday)) {
                    continue;
                }
                $birth    = new DateTime($p->Birthday);
                $interval = $now->diff($birth);
                $diff     = $interval->format('%y');
                if ($diff < $ageLimit) {
                    continue;
                }
            }
            $res[$p->ID] = $p->DisplayName;
        }
        asort($res);

        return $res;
    }
}
