<?php

namespace Model\Event\ReadModel;

use Model\Event\Person;
use Nette\StaticClass;

final class PersonFactory
{

    use StaticClass;

    public static function create(\stdClass $function): ?Person
    {
        if ($function->ID_Person === NULL) {
            return NULL;
        }

        return new Person(
            $function->ID_Person,
            $function->Person,
            $function->Email ?? ''
        );
    }

}
