<?php

declare(strict_types=1);

namespace Model\Payment\IntegrationTests;

use Model\Payment\Commands\Group\RemoveGroup;
use Model\Payment\Group;
use Model\Payment\GroupNotFoundException;
use Model\Payment\Handlers\Group\RemoveGroupHandler;
use Model\Payment\Payment;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Repositories\IPaymentRepository;

final class RemoveGroupTest extends \IntegrationTest
{

    /** @var IGroupRepository */
    private $groups;

    /** @var IPaymentRepository */
    private $payments;

    /** @var RemoveGroupHandler */
    private $handler;

    protected function getTestedEntites(): array
    {
        return [
            Group::class,
            Group\Email::class,
            Payment::class,
        ];
    }

    protected function _before()
    {
        $this->tester->useConfigFiles([__DIR__ . '/RemoveGroupTest.neon']);
        parent::_before();
        $this->groups = $this->tester->grabService(IGroupRepository::class);
        $this->payments = $this->tester->grabService(IPaymentRepository::class);
        $this->handler = $this->tester->grabService(RemoveGroupHandler::class);
    }

    public function test(): void
    {
        $group = new Group(
            123,
            NULL,
            'test',
            \Helpers::createEmptyPaymentDefaults(),
            new \DateTimeImmutable(),
            \Helpers::createEmails(),
            NULL,
            NULL
        );

        $group->close(''); // only closed groups can be removed

        $this->groups->save($group);

        // just to make sure it got the right ID
        $this->assertSame(1, $group->getId());

        for($i = 1; $i <= 3; $i++) {
            $this->payments->save(
                new Payment($group, 'test' . $i, NULL, 120, \Helpers::getValidDueDate(), NULL, NULL, NULL, '')
            );
        }

        $this->handler->handle(new RemoveGroup(1));

        $this->assertEmpty($this->payments->findByGroup(1));

        $this->expectException(GroupNotFoundException::class);

        $this->groups->find(1);
    }

}
