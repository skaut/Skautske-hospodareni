<?php

declare(strict_types=1);

namespace App\Presentation\Travel\Vehicle;

use App\BasePresenter;
use App\Model\Travel\TravelService;
use App\Model\Unit\UnitService;
use App\Presentation\Travel\Vehicle\Factories\IRoadworthyControlFactory;
use Codeception\Test\Unit;
use Latte\Engine;
use Mockery;
use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Forms\Controls\SelectBox;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Nette\Utils\FileSystem;
use ReflectionProperty;

use function sys_get_temp_dir;

final class VehiclePresenterTest extends Unit
{
    private ?Session $session = null;

    public function testNewVehicleFormWithTamperedFuelTypeShowsCombustionConsumptionUnit(): void
    {
        $unitService = Mockery::mock(UnitService::class);
        $unitService->shouldReceive('getSubunitPairs')->andReturn([]);

        $presenter = new VehiclePresenter(Mockery::mock(TravelService::class), Mockery::mock(IRoadworthyControlFactory::class));
        $request = new Request(new UrlScript('http://localhost/'));
        $response = new Response();
        $sessionSavePath = sys_get_temp_dir().'/hskauting-vehicle-presenter-test-sessions';
        FileSystem::createDir($sessionSavePath);
        $this->session = (new Session($request, $response))->setSavePath($sessionSavePath);
        $presenter->injectPrimary($request, $response, session: $this->session);
        (new ReflectionProperty(BasePresenter::class, 'unitService'))->setValue($presenter, $unitService);
        (new ReflectionProperty(BasePresenter::class, 'unitId'))->setValue($presenter, 27266);
        (new ReflectionProperty(Control::class, 'template'))->setValue($presenter, new DefaultTemplate(new Engine()));

        // A posted value outside the select items makes Nette return null from getValue().
        $fuelType = $presenter['formCreateVehicle']['fuelType'];
        self::assertInstanceOf(SelectBox::class, $fuelType);
        $fuelType->checkDefaultValue(false)->setValue('hybrid');

        $presenter->renderNew();

        self::assertSame('l/100 km', $presenter->getTemplate()->consumptionUnit);
    }

    protected function _after(): void
    {
        // Další testy si nastavují vlastní session.save_path, to jde jen u zavřené session.
        $this->session?->close();
        Mockery::close();
    }
}
