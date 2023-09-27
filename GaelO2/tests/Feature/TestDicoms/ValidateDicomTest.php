<?php

namespace Tests\Feature\TestDicoms;

use App\GaelO\Constants\Constants;
use App\GaelO\Services\TusService;
use App\Models\ReviewStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\AuthorizationTools;

class ValidateDicomTest extends TestCase
{

    use RefreshDatabase;

    private ReviewStatus $reviewStatus;
    private string $studyName;
    private int $visitId;
    private array $tusIdArray;
    private int $numberOfInstances;

    protected function setUp() : void{

        $this->markTestSkipped('Needs both Orthanc servers to run');
        parent::setUp();
        $this->artisan('db:seed');
        $this->reviewStatus = ReviewStatus::factory()->create();
        $this->studyName = $this->reviewStatus->visit->patient->study->name;
        $this->visitId = $this->reviewStatus->visitId;

        $mockTusService = $this->partialMock(TusService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFile')
            ->andReturnUsing(function () {
                copy((getcwd() . "/tests/data/MR.zip"), (getcwd() . "/tests/data/MR2.zip"));
                chmod((getcwd() . "/tests/data/MR2.zip"),0777); 
                return (getcwd() . "/tests/data/MR2.zip");
            });
            $mock->shouldReceive('deleteFile')
            ->andReturn(null);
        });
        app()->instance(TusService::class, $mockTusService);

        $this->tusIdArray = ['c80f0bd67443e65d84ed663b37adf146'];
        $this->numberOfInstances = 22;
    }


    public function testValidateDicom()
    {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $this->studyName);
        $payload = [
            'visitId'=>1,
            'originalOrthancId'=>'7d2804c1-a17e7902-9a04d3fd-03e67d58-5ff3b85f',
            'uploadedFileTusId'=>$this->tusIdArray,
            'numberOfInstances'=>$this->numberOfInstances
        ];

        $response = $this->json('POST', 'api/visits/'.$this->visitId.'/validate-dicom', $payload);
        $response->assertStatus(200);


    }

    public function testValidateDicomShouldBeForbidden()
    {
        $payload = [
            'visitId'=>1,
            'originalOrthancId'=>'7d2804c1-a17e7902-9a04d3fd-03e67d58-5ff3b85f',
            'uploadedFileTusId'=>$this->tusIdArray,
            'numberOfInstances'=>$this->numberOfInstances
        ];

        $this->json('POST', 'api/visits/'.$this->visitId.'/validate-dicom', $payload)->assertStatus(403);


    }
}
