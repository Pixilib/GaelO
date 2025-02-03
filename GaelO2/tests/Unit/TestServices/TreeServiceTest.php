<?php

namespace Tests\Unit\TestServices;

use App\GaelO\Repositories\VisitRepository;
use App\GaelO\Services\TreeService\ControllerTreeService;
use App\GaelO\Services\TreeService\MonitorTreeService;
use App\Models\Study;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class TreeServiceTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function testTreeServiceControllerWithShowAll()
    {
        $visitRepository = $this->spy(VisitRepository::class);
        app()->instance(VisitRepository::class, $visitRepository);

        $controllerTreeService = App::make(ControllerTreeService::class);
        $study = Study::factory()->controllerShowAll()->create();
        $controllerTreeService->setUserAndStudy(1, $study->name);
        $controllerTreeService->buildTree();

        $visitRepository->shouldHaveReceived('getVisitsInStudyNeedingQualityControl')->once();
    }

    public function testTreeServiceControllerWithoutShowAll()
    {
        $visitRepository = $this->spy(VisitRepository::class);
        app()->instance(VisitRepository::class, $visitRepository);

        $controllerTreeService = App::make(ControllerTreeService::class);
        $study = Study::factory()->create();
        $controllerTreeService->setUserAndStudy(1, $study->name);
        $controllerTreeService->buildTree();

        $visitRepository->shouldHaveReceived('getVisitsInStudyAwaitingControllerAction')->once();
    }

    public function testTreeServiceMonitorWithShowAll()
    {
        $visitRepository = $this->spy(VisitRepository::class);
        app()->instance(VisitRepository::class, $visitRepository);

        $controllerTreeService = App::make(MonitorTreeService::class);
        $study = Study::factory()->monitorShowAll()->create();
        $controllerTreeService->setUserAndStudy(1, $study->name);
        $controllerTreeService->buildTree();

        $visitRepository->shouldHaveReceived('getVisitsInStudy')->once();
    }

    public function testTreeServiceMonitorWithoutShowAll()
    {
        $visitRepository = $this->spy(VisitRepository::class);
        app()->instance(VisitRepository::class, $visitRepository);

        $controllerTreeService = App::make(MonitorTreeService::class);
        $study = Study::factory()->create();
        $controllerTreeService->setUserAndStudy(1, $study->name);
        $controllerTreeService->buildTree();

        $visitRepository->shouldHaveReceived('getVisitsFromPatientIdsWithContext')->once();
    }

    

}