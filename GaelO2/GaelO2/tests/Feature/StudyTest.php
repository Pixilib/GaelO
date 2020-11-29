<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use App\OrthancStudy;
use App\Patient;
use App\ReviewStatus;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use App\Study;
use App\Visit;
use App\VisitGroup;
use App\VisitType;
use Tests\AuthorizationTools;

class StudyTest extends TestCase
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
    }

    public function testCreateStudy() {
        $payload = [
            'studyName'=>'NewStudy',
            'patientCodePrefix'=>'1234'
        ];
        $this->json('POST', '/api/studies', $payload)->assertNoContent(201);

        //Check that created study is available
        $studyEntity = Study::where('name', 'NewStudy')->get()->toArray();
        $this->assertEquals('NewStudy',$studyEntity[0]['name']);
        $this->assertEquals('1234',$studyEntity[0]['patient_code_prefix']);
    }

    public function testCreateStudyForbiddenNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $payload = [
            'studyName'=>'NewStudy',
            'patientCodePrefix'=>'1234'
        ];
        $this->json('POST', '/api/studies', $payload)->assertStatus(403);
    }

    public function testCreateAlreadyExistingStudy(){
        $study = factory(Study::class, 2)->create()->first();
        $payload = [
            'studyName'=>$study->name,
            'patientCodePrefix'=>'1234'
        ];
        $this->json('POST', '/api/studies', $payload)->assertNoContent(409);
    }

    public function testGetStudyWithDetails(){
        $study = factory(Study::class, 2)->create();

        $study->each(function ($study) {
            $visitGroups = factory(VisitGroup::class)->create(['study_name'=>$study->name], 3);
            $visitGroups->each(function ($visitGroup) {
                factory(VisitType::class)->create(['visit_group_id'=>$visitGroup->id], 3);
            });
        });

        $response = $this->json('GET', '/api/studies?expand')->assertSuccessful();
        $response->assertJsonCount(2);

    }

    public function testGetStudies(){
        factory(Study::class, 1)->create();
        $this->json('GET', '/api/studies')->assertJsonCount(1);
    }

    public function testGetStudiesForbiddenNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        factory(Study::class, 1)->create();
        $this->json('GET', '/api/studies')->assertStatus(403);
    }

    public function testGetDeletedStudies(){
        $study = factory(Study::class, 1)->create();
        $study->first()->delete();
        $response = $this->json('GET', '/api/studies')->content();
        $response = json_decode($response, true);
        $this->assertTrue($response[0]['deleted']);
    }

    public function testReactivateStudy(){
        $study = factory(Study::class, 1)->create();
        $studyName = $study->first()->name;
        $study->first()->delete();
        $payload = [];
        $this->json('PATCH', '/api/studies/'.$studyName.'/reactivate', $payload)->assertNoContent(200);
        $reactivatedStudy = Study::find($studyName)->first()->toArray();
        //Check study is now undeleted
        $this->assertNull($reactivatedStudy['deleted_at']);

    }

    public function testReactivateStudyForbiddenNotAdmin(){
        AuthorizationTools::actAsAdmin(false);
        $study = factory(Study::class, 1)->create()->first();
        $study->first()->delete();
        $payload = [];
        $this->json('PATCH', '/api/studies/'.$study->name.'/reactivate', $payload)->assertStatus(403);
    }

    public function testIsKnownOrthancStudyIDForbidden(){
        $study = factory(Study::class, 1)->create();
        $studyName = $study->first()->name;
        $this->json('GET', '/api/studies/'.$studyName.'/orthanc-study-id/WrongOrthancID')->assertStatus(403);
    }

    public function testIsKnownOrthancStudyIDNot(){
        $study = factory(Study::class, 1)->create();
        $studyName = $study->first()->name;
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $studyName);
        $this->json('GET', '/api/studies/'.$studyName.'/orthanc-study-id/WrongOrthancID')->assertStatus(404);
    }

    public function testIsKnownOrthancStudyIDYes(){

        $study = factory(Study::class, 1)->create()->first();
        $visitGroup = factory(VisitGroup::class)->create(['study_name' => $study->name]);
        $visitType = factory(VisitType::class)->create(['visit_group_id' => $visitGroup['id']]);
        $patient = factory(Patient::class)->create(['code' => 12341234123412, 'study_name' => $study->name, 'center_code' => 0]);
        $visit = factory(Visit::class)->create(['creator_user_id' => 1,
        'patient_code' => $patient['code'],
        'visit_type_id' => $visitType['id'],
        'status_done' => 'Done']);
        $reviewStatus = factory(ReviewStatus::class)->create([
            'visit_id' => $visit->id,
            'study_name'=> $study->name,
        ]);
        factory(OrthancStudy::class)->create([
            'orthanc_id'=>'7d2804c1-a17e7902-9a04d3fd-03e67d58-5ff3b85f',
            'visit_id' => $visit->id,
            'uploader_id'=> 1,
        ]);
        $studyName = $study->name;
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_INVESTIGATOR, $studyName);
        $this->json('GET', '/api/studies/'.$studyName.'/orthanc-study-id/7d2804c1-a17e7902-9a04d3fd-03e67d58-5ff3b85f')->assertStatus(200);
    }


}
