<?php

namespace Tests\Feature;

use App\OrthancSeries;
use App\OrthancStudy;
use App\User;
use App\VisitGroup;
use App\VisitType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use App\Patient;
use App\ReviewStatus;
use App\Study;
use Tests\TestCase;
use App\Visit;

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

        $this->study = factory(Study::class)->create(['name' => 'test', 'patient_code_prefix' => 1234]);
        $this->visitGroup = factory(VisitGroup::class)->create(['study_name' => 'test']);
        $this->visitType = factory(VisitType::class)->create(['visit_group_id' => $this->visitGroup['id']]);
        $this->patient = factory(Patient::class)->create(['code' => 12341234123412, 'study_name' => 'test', 'center_code' => 0]);
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
        }

    }


    public function testValidateDicom()
    {
        $payload = [
            'visitId'=>1,
            'originalOrthancId'=>'7d2804c1-a17e7902-9a04d3fd-03e67d58-5ff3b85f',
            'uploadedFileTusId'=>['', ''],
            'numberOfInstance'=>150
        ];

        $response = $this->json('POST', 'api/visits/'.$this->visit->id.'/validate-dicom', $payload);
        $response->assertSuccessful();
        $orthancStudy = OrthancStudy::where('visit_id', $this->visit->id)->first()->series()->get()->toArray();

        dd($orthancStudy);
    }
}
