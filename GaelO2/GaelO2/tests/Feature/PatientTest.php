<?php

namespace Tests\Feature;

use App\Patient;
use App\Study;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
        //Test get patient 4
        $this->json('GET', '/api/patients/12345671234567')
            ->assertStatus(200);
        //Test get all patients
        $this->json('GET', '/api/patients')-> assertJsonCount(6);
        //Test get incorrect patient
        $resp = $this->json('GET', '/api/patients/-1') -> assertStatus(404); //No query result for this model
    }

    public function testGetPatientFromStudy() {
        factory(Study::class)->create(['name'=>'test']);
        factory(Patient::class, 5)->create(['center_code'=>0, 'study_name'=>'test']);
        $this->json('GET', '/api/studies/test/patients')
            ->assertStatus(200);
    }
}
