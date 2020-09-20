<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use App\Study;
use App\VisitGroup;
use App\VisitType;

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

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateStudy() {
        $payload = [
            'studyName'=>'NewStudy',
            'patientCodePrefix'=>'1234'
        ];
        $this->json('POST', '/api/studies', $payload)->assertNoContent(201);
        //Second creation of the same study should not be allowed
        $this->json('POST', '/api/studies', $payload)->assertNoContent(409);
        //Check that created study is available
        $studyEntity = Study::where('name', 'NewStudy')->get()->toArray();
        $this->assertEquals('NewStudy',$studyEntity[0]['name']);
        $this->assertEquals('1234',$studyEntity[0]['patient_code_prefix']);
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
        $response = $this->json('GET', '/api/studies')->assertJsonCount(1);
    }

    public function testGetDeletedStudies(){
        $study = factory(Study::class, 1)->create();
        $study->first()->delete();
        $response = $this->json('GET', '/api/studies')->content();
        $response = json_decode($response, true);
        $this->assertTrue($response[0]['deleted']);
    }



}
