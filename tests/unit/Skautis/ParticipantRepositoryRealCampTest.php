<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use App\Model\Event\SkautisCampId;
use App\Model\Participant\Payment\Event;
use App\Model\Participant\Payment\EventType;
use App\Model\Participant\Repositories\IPaymentRepository;
use Codeception\Test\Unit;
use Mockery;
use Skautis\Skautis;
use stdClass;

final class ParticipantRepositoryRealCampTest extends Unit
{
    public function testFindRealByCampRequestsOnlyRealParticipants(): void
    {
        $eventService = new class {
            /** @var array<string, int|bool> */
            public array $arguments = [];

            /** @return stdClass[] */
            public function ParticipantCampAll(array $arguments): array
            {
                $this->arguments = $arguments;

                return [(object) [
                    'ID' => 1,
                    'ID_Person' => 2,
                    'Person' => 'Novák Jan',
                    'Street' => 'Skautská 1',
                    'City' => 'Praha',
                    'Postcode' => 10000,
                    'Days' => 3,
                    'IsAccepted' => true,
                ]];
            }
        };
        $skautis = new class($eventService) extends Skautis {
            public function __construct(private object $eventService)
            {
            }

            public function getWebService($name): object
            {
                return $this->eventService;
            }
        };
        $payments = Mockery::mock(IPaymentRepository::class);
        $payments
            ->shouldReceive('findByEvent')
            ->once()
            ->withArgs(static fn (Event $event): bool => $event->getId() === 42 && $event->getType()->equals(EventType::CAMP()))
            ->andReturn([]);

        $participants = (new ParticipantRepository($skautis, $payments))->findRealByCamp(new SkautisCampId(42));

        self::assertSame(['ID_EventCamp' => 42, 'Real' => true], $eventService->arguments);
        self::assertCount(1, $participants);
    }
}
