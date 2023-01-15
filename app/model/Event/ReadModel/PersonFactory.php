<?php

declare(strict_types=1);

namespace Model\Event\ReadModel;

use Model\Event\Person;
use Nette\StaticClass;
use stdClass;

final class PersonFactory
{
    use StaticClass;

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public static function create(stdClass $function): Person|null
    {
        if ($function->ID_Person === null) {
            return null;
        }

        return new Person(
            $function->ID_Person,
            $function->Person,
            $function->Email ?? '',
        );
    }
}
