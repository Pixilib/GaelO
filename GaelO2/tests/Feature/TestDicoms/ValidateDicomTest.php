<?php

namespace Tests\Feature\TestDicoms;

use App\GaelO\Constants\Constants;
use App\Models\ReviewStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\AuthorizationTools;

class ValidateDicomTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp() : void{

        $this->markTestSkipped('Ces tests son a revoir et necessitent le reste de la stack technique');
        parent::setUp();
        $this->artisan('db:seed');
        $this->reviewStatus = ReviewStatus::factory()->create();

        $this->studyName = $this->reviewStatus->visit->patient->study->name;
        $this->visitId = $this->reviewStatus->visitId;

        $this->tusIdArray = ['c80f0bd67443e65d84ed663b37adf146'];
        $this->numberOfInstances = 326;

        $this->markTestSkipped('all tests in this file are invactive, this is only to check orthanc communication');

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
