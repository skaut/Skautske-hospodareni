<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use App\Model\Event\SkautisCampId;
use App\Model\Participant\Payment\Event;
use App\Model\Participant\Payment\EventType;
use App\Model\Participant\Repositories\IPaymentRepository;
use Codeception\Test\Unit;
use Mockery;
use Skaut\Skautis\Skautis;
use Skaut\Skautis\Wsdl\WebServiceInterface;

final class ParticipantRepositoryRealCampTest extends Unit
{
    public function testFindRealByCampRequestsOnlyRealParticipants(): void
    {
        $eventService = new class implements WebServiceInterface {
            public string $functionName = '';

            /** @var array<int|string, mixed> */
            public array $arguments = [];

            /** @param array<int|string, mixed> $arguments */
            public function call(string $functionName, array $arguments = []): mixed
            {
                $this->functionName = $functionName;
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

            /** @param array<int|string, mixed> $arguments */
            public function __call(string $functionName, array $arguments): mixed
            {
                return $this->call($functionName, $arguments[0] ?? []);
            }
        };
        $skautis = new class($eventService) extends Skautis {
            public function __construct(private WebServiceInterface $eventService)
            {
            }

            public function getWebService(string $name): WebServiceInterface
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

        self::assertSame('ParticipantCampAll', $eventService->functionName);
        self::assertSame(['ID_EventCamp' => 42, 'Real' => true], $eventService->arguments);
        self::assertCount(1, $participants);
    }
}
