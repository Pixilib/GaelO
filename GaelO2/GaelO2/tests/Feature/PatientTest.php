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
use Tests\AuthorizationTools;

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

        //Fill patient table
        $this->study = factory(Study::class)->create();
        factory(Patient::class)->create(['code'=>12345671234567, 'center_code'=>0, 'study_name'=>$this->study->name]);

    }

    public function testGetPatient() {

        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, $this->study->name);

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

    }

    public function testGetPatientFailNotSupervisor(){
        $this->json('GET', '/api/patients/12345671234567?role=Supervisor')->assertStatus(403);
    }

    public function testGetIncorrectPatientShouldFail(){
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, $this->study->name);
        $this->json('GET', '/api/patients/-1?role=Supervisor') -> assertStatus(404);
    }

    public function testGetPatientFromStudy() {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, $this->study->name);
        for($i=1; $i<6; $i++){
            factory(Patient::class)->create(['code'=>(12345671234567)+$i, 'center_code'=>0, 'study_name'=>$this->study->name]);
        }
        $this->json('GET', '/api/studies/'.$this->study->name.'/patients?role=Supervisor')
        -> assertJsonCount(6);
    }

    public function testModifyPatient(){
        $patient = factory(Patient::class)->create(['code'=>(22345671234567), 'center_code'=>0, 'study_name'=>$this->study->name]);

        $payload = [
            'firstname'=> 'a',
            'lastname'=>'b',
            'gender' => 'M',
            'birthDay'=> 5,
            'birthMonth'=> 10,
            'birthYear'=> 1955,
            'registrationDate'=>'12312020',
            'investigatorName'=> 'salim',
            'centerCode'=>0
        ];

        dd($this->json('PATCH', '/api/patients/'.$patient->code, $payload));
        //SK A Faaire assertion sur la db
        //Check role supervisor
    }

    public function testModifyPatientWithdraw(){

    }
}
