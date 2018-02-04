<?php

namespace PHPSTORM_META {

    use _generated\IntegrationTesterActions;
    use Nette\DI\Container;
    use Doctrine\ORM\EntityManager;

    override(Container::getByType(0),
        map([
            '' => '@',
        ])
    );

    override(IntegrationTesterActions::grabService(0),
        map([
            '' => '@',
        ])
    );

    override(EntityManager::find(0),
        map([
            '' => '@',
        ])
    );

    override(\Mockery::mock(0),
        map([
            '' => '@',
        ])
    );
}
