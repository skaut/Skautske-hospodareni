<?php

namespace PHPSTORM_META {

    use Nette\DI\Container;
    use Doctrine\ORM\EntityManager;

    override(Container::getByType(0),
        map([
            '' => '@',
        ])
    );

    override(\IntegrationTester::grabService(0),
        map([
            '' => '@',
        ])
    );

    override(EntityManager::find(0),
        map([
            '' => '@',
        ])
    );
}
