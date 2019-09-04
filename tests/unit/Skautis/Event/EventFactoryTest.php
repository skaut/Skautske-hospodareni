<?php

declare(strict_types=1);

namespace Model\Skautis\Event;

use Cake\Chronos\Date;
use Codeception\Test\Unit;
use Model\Common\UnitId;
use Model\Event\SkautisEventId;
use Model\Skautis\Factory\EventFactory;
use stdClass;

final class EventFactoryTest extends Unit
{
    public function testDraftEventCreation() : void
    {
        $factory = new EventFactory();
        $event   = $factory->create($this->getDraftEvent());
        $this->assertEquals(new SkautisEventId(1402), $event->getId());
        $this->assertEquals(new UnitId(27266), $event->getUnitId());
        $this->assertTrue($event->getStartDate() instanceof Date);
        $this->assertTrue($event->getEndDate() instanceof Date);
    }

    public function testClosedEventCreation() : void
    {
        $factory = new EventFactory();
        $event   = $factory->create($this->getClosedEvent());
        $this->assertEquals('Jan Novák (Joe)', $event->getPersonClosed());
        $this->assertEquals(Date::createFromDate(2019, 9, 3), $event->getDateClosed());
    }

    private function getDraftEvent() : stdClass
    {
        return (object) [
            'ID_Login'=>'00000000-0000-0000-0000-000000000000',
            'ID' => 1402,
            'ID_Group' => 770448,
            'ID_EventType' => 'general',
            'ID_UserCreate' => 2465,
            'DateCreate' => '2019-08-25T14:13:46.363',
            'DisplayName' => 'Oddílová',
            'ID_Unit' => 27266,
            'Unit' => 'Sinovo středisko',
            'RegistrationNumber' => '621.66',
            'StartDate' => '2019-08-25T00:00:00',
            'EndDate' => '2019-08-25T00:00:00',
            'TotalDays' => 1,
            'GpsLatitude' => null,
            'GpsLatitudeText' => '',
            'GpsLongitude' => null,
            'GpsLongitudeText' => '',
            'Location' => ' ',
            'Note' => ' ',
            'ID_Event' => 2388,
            'Event' => 'Acceptance test event 1566735218',
            'ID_EventGeneralState' => 'draft',
            'EventGeneralState' => 'Rozpracováno',
            'ID_UnitEducative' => null,
            'ID_EventGeneralType' => 2,
            'EventGeneralType' => '2) Výprava',
            'ID_EventGeneralScope' => 2,
            'EventGeneralScope' => 'Oddílová',
            'ForeignParticipants' => null,
            'LeaderParticipants' => null,
            'AssistantParticipants' => null,
            'IsStatisticAutoComputed' => false,
            'ChildDays' => 0,
            'PersonDays' => 0,
            'ID_PersonClosed' => null,
            'DateClosed' => null,
            'ID_EventSpecificationTypeArray' => (object) [],
            'EventSpecificationType' => '',
            'IsAutoComputedDays' => true,
            'TotalParticipants' => 0,
        ];
    }

    private function getClosedEvent() : stdClass
    {
        return (object) [
            'ID_Login' => '00000000-0000-0000-0000-000000000000',
            'ID' => 1357,
            'ID_Group' => 770448,
            'ID_EventType' => 'general',
            'ID_UserCreate' => 2465,
            'DateCreate' => '2019-08-18T22:16:42.817',
            'DisplayName' => 'Acceptance test event 1566159338',
            'ID_Unit' => 27266,
            'Unit' => 'Sinovo středisko',
            'RegistrationNumber' => '621.66',
            'StartDate' => '2019-08-18T00:00:00',
            'EndDate' => '2019-08-18T00:00:00',
            'TotalDays' => 1,
            'GpsLatitude' => null,
            'GpsLatitudeText' => '',
            'GpsLongitude' => null,
            'GpsLongitudeText' => '',
            'Location' => ' ',
            'Note' => ' ',
            'ID_Event' => 2341,
            'Event' => 'Acceptance test event 1566159338',
            'ID_EventGeneralState' => 'closed',
            'EventGeneralState' => 'Uzavřeno',
            'ID_UnitEducative' => null,
            'ID_EventGeneralType' => 2,
            'EventGeneralType' => '2) Výprava',
            'ID_EventGeneralScope' => 2,
            'EventGeneralScope' => 'Oddílová',
            'ForeignParticipants' => null,
            'LeaderParticipants' => null,
            'AssistantParticipants' => null,
            'IsStatisticAutoComputed' => false,
            'ChildDays' => 0,
            'PersonDays' => 0,
            'ID_PersonClosed' => 123,
            'PersonClosed' => 'Jan Novák (Joe)',
            'DateClosed' => '2019-09-03T11:35:36.49',
            'ID_EventSpecificationTypeArray' => (object) [],
            'EventSpecificationType' => '',
            'IsAutoComputedDays' => true,
            'TotalParticipants' => 0,
        ];
    }
}
