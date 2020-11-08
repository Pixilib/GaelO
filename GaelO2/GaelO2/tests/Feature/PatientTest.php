<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use App\GaelO\UseCases\GetPatient\PatientEntity;
use App\Patient;
use App\Study;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;

class PatientTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

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

    public function testCreatePatient() {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function testGetPatient() {
        //Fill patient table
        factory(Study::class)->create(['name'=>'test']);
        factory(Patient::class)->create(['code'=>12345671234567, 'center_code'=>0, 'study_name'=>'test']);
        factory(Patient::class, 5)->create(['center_code'=>0, 'study_name'=>'test']);
        ImportPatientTest::addRoleToUser(1, Constants::ROLE_SUPERVISOR, 'test');

        //Test get patient 4
        $response = $this->json('GET', '/api/patients/12345671234567?role=Supervisor')
            ->content();
        $response = json_decode($response, true);

        //Check all Item in patientEntity are present in reponse
        foreach ( get_class_vars(PatientEntity::class) as $key=>$value ){
            //Camelize keys
            $key = str_replace('_', '', lcfirst(ucwords($key, '_')));
            $this->assertArrayHasKey($key, $response);
        }

        //Test get all patients
        $this->json('GET', '/api/studies/test/patients?role=Supervisor')-> assertJsonCount(6);
        //Test get incorrect patient
        $resp = $this->json('GET', '/api/patients/-1?role=Supervisor') -> assertStatus(404); //No query result for this model
    }

    public function testGetPatientFromStudy() {
        factory(Study::class)->create(['name'=>'test']);
        factory(Patient::class, 5)->create(['center_code'=>0, 'study_name'=>'test']);
        ImportPatientTest::addRoleToUser(1, Constants::ROLE_SUPERVISOR, 'test');
        $this->json('GET', '/api/studies/test/patients?role=Supervisor')
            ->assertStatus(200);
    }
}
