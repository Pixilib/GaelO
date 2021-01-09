<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use App\Model\User;
use App\Model\VisitGroup;
use App\Model\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use App\Model\Patient;
use App\Model\ReviewStatus;
use App\Model\Study;
use Tests\TestCase;
use App\Model\Visit;
use Tests\AuthorizationTools;

class ValidateDicomTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp() : void{
        parent::setUp();

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );

        $this->study = factory(Study::class)->create(['patient_code_prefix' => 1234]);
        $this->visitGroup = factory(VisitGroup::class)->create(['study_name' => $this->study->name]);
        $this->visitType = factory(VisitType::class)->create(['visit_group_id' => $this->visitGroup['id']]);
        $this->patient = factory(Patient::class)->create(['code' => 12341234123412, 'study_name' => $this->study->name, 'center_code' => 0]);
        $this->visit = factory(Visit::class)->create(['creator_user_id' => 1,
        'patient_code' => $this->patient['code'],
        'visit_type_id' => $this->visitType['id'],
        'status_done' => 'Done']);
        $this->reviewStatus = factory(ReviewStatus::class)->create([
            'visit_id' => $this->visit->id,
            'study_name'=> $this->study->name,
        ]);

        if (true) {
            $this->markTestSkipped('all tests in this file are invactive, this is only to check orthanc communication');
        }else{
            $this->tusIdArray = ['c80f0bd67443e65d84ed663b37adf146'];
            $this->numberOfInstances = 326;
        }

    }


    public function testValidateDicom()
    {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $payload = [
            'visitId'=>1,
            'originalOrthancId'=>'7d2804c1-a17e7902-9a04d3fd-03e67d58-5ff3b85f',
            'uploadedFileTusId'=>$this->tusIdArray,
            'numberOfInstances'=>$this->numberOfInstances
        ];

        $response = $this->json('POST', 'api/visits/'.$this->visit->id.'/validate-dicom', $payload);
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

        $this->json('POST', 'api/visits/'.$this->visit->id.'/validate-dicom', $payload)->assertStatus(403);


    }
}
