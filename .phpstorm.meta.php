<?php

namespace PHPSTORM_META {

    use Nette\DI\Container;

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

}
