<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use App\Model\DTO\Participant\NonMemberParticipant;
use App\Model\Event\SkautisCampId;
use App\Model\Event\SkautisEventId;
use App\Model\Participant\Repositories\IPaymentRepository;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use Mockery;
use Skautis\Skautis;
use Skautis\Wsdl\WebServiceInterface;

final class ParticipantRepositoryCreateTest extends Unit
{
    public function testCreatingCampNonMemberSendsSexToSkautis(): void
    {
        $eventWebService = Mockery::mock(WebServiceInterface::class);
        $eventWebService->shouldReceive('ParticipantCampInsert')
            ->once()
            ->with([
                'ID_EventCamp' => 42,
                'Person' => [
                    'FirstName' => 'Jana',
                    'LastName' => 'Nováková',
                    'NickName' => null,
                    'Note' => '',
                    'IdentificationCode' => '',
                    'IsForeign' => false,
                ],
            ])
            ->andReturn((object) ['ID_Person' => 101]);

        $orgWebService = Mockery::mock(WebServiceInterface::class);
        $orgWebService->shouldReceive('PersonUpdateBasic')
            ->once()
            ->with([
                'ID' => 101,
                'FirstName' => 'Jana',
                'LastName' => 'Nováková',
                'IdentificationCode' => null,
                'ID_Sex' => 'female',
                'Birthday' => '2012-05-10T00:00:00',
                'Street' => 'Skautská 1',
                'City' => 'Praha',
                'Postcode' => 10000,
            ]);

        $skautis = Mockery::mock(Skautis::class);
        $skautis->shouldReceive('getWebService')->with('event')->andReturn($eventWebService);
        $skautis->shouldReceive('getWebService')->with('org')->andReturn($orgWebService);

        $repository = new ParticipantRepository($skautis, Mockery::mock(IPaymentRepository::class));
        $repository->createCampParticipant(
            new SkautisCampId(42),
            new NonMemberParticipant(
                'Jana',
                'Nováková',
                null,
                'female',
                new ChronosDate('2012-05-10'),
                'Skautská 1',
                'Praha',
                10000,
            ),
        );
    }

    public function testCreatingGeneralEventNonMemberSendsSexToSkautis(): void
    {
        $eventWebService = Mockery::mock(WebServiceInterface::class);
        $eventWebService->shouldReceive('ParticipantGeneralInsert')
            ->once()
            ->andReturn((object) ['ID_Person' => 102]);

        $orgWebService = Mockery::mock(WebServiceInterface::class);
        $orgWebService->shouldReceive('PersonUpdateBasic')
            ->once()
            ->with(Mockery::on(static fn (array $data): bool => $data['ID'] === 102 && $data['ID_Sex'] === 'male'));

        $skautis = Mockery::mock(Skautis::class);
        $skautis->shouldReceive('getWebService')->with('event')->andReturn($eventWebService);
        $skautis->shouldReceive('getWebService')->with('org')->andReturn($orgWebService);

        $repository = new ParticipantRepository($skautis, Mockery::mock(IPaymentRepository::class));
        $repository->createEventParticipant(
            new SkautisEventId(43),
            new NonMemberParticipant(
                'Petr',
                'Novák',
                null,
                'male',
                null,
                'Skautská 2',
                'Brno',
                60200,
            ),
        );
    }
}
