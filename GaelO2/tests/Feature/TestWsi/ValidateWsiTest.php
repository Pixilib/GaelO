<?php

namespace Tests\Feature\TestWsi;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\Enums\InvestigatorFormStateEnum;
use App\GaelO\Constants\Enums\QualityControlStateEnum;
use App\GaelO\Services\TusService;
use App\Models\Patient;
use App\Models\ReviewStatus;
use App\Models\Visit;
use App\Models\VisitGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\AuthorizationTools;

class ValidateWsiTest extends TestCase
{

    use RefreshDatabase;

    private ReviewStatus $reviewStatus;
    private string $studyName;
    private int $visitId;
    private array $tusIdArray;
    private int $patientCenterCode;
    private int $numberOfInstances;
    private Patient $patient;
    private VisitGroup $visitGroup;
    private Visit $visit;

    protected function setUp() : void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->reviewStatus = ReviewStatus::factory()->create();
        $this->studyName = $this->reviewStatus->visit->patient->study->name;
        $this->patientCenterCode = $this->reviewStatus->visit->patient->center_code;
        $this->visitId = $this->reviewStatus->visit_id;

        $mockTusService = $this->partialMock(TusService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFile')
            ->andReturnUsing(function () {
                copy((getcwd() . "/tests/data/wsi-sample"), (getcwd() . "/tests/data/wsi-sample2"));
                chmod((getcwd() . "/tests/data/wsi-sample2"),0777); 
                return (getcwd() . "/tests/data/wsi-sample2");
            });
            $mock->shouldReceive('deleteFile')
            ->andReturn(null);
            $mock->shouldReceive('getMetadata')
            ->andReturn([]);
        });
        app()->instance(TusService::class, $mockTusService);

        $this->tusIdArray = ['c80f0bd67443e65d84ed663b37adf146'];
    }


    public function testValidateWsi()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->studyName);
        AuthorizationTools::addAffiliatedCenter($currentUserId, $this->patientCenterCode);
        $payload = [
            'uploadedFileTusId'=>$this->tusIdArray,
        ];

        $response = $this->json('POST', 'api/visits/'.$this->visitId.'/validate-wsi', $payload);
        $response->assertStatus(200);


    }

    /*
    public function testValidateWsiShouldBeForbidden()
    {
        $payload = [
            'uploadedFileTusId'=>$this->tusIdArray,
        ];

        $this->json('POST', 'api/visits/'.$this->visitId.'/validate-wsi', $payload)->assertStatus(403);


    }
        */
}
