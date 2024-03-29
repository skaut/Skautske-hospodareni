<?php

declare(strict_types=1);

namespace Model\Skautis;

use Doctrine\ORM\EntityManager;
use Hskauting\Tests\SkautisTest;
use Model\Event\SkautisEducationId;
use Model\Infrastructure\Repositories\Participant\PaymentRepository;
use Model\Participant\Payment;
use VCR\VCR;

final class ParticipantRepositoryTest extends SkautisTest
{
    /** @return string[] */
    public function getTestedAggregateRoots(): array
    {
        return [
            Payment::class,
        ];
    }

    public function testFindByEducationReturnsAllParticipants(): void
    {
        VCR::insertCassette('ParticipantRepository/findByEducation_all.json');

        $participants = $this->getRepository()->findByEducation(new SkautisEducationId(1621));

        $this->assertCount(3, $participants);
    }

    private function getRepository(): ParticipantRepository
    {
        return new ParticipantRepository($this->createSkautis('b5b0267c-3add-4e7a-9524-c5b30e0a4eca'), new PaymentRepository($this->tester->grabService(EntityManager::class)));
    }
}
