<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use App\Models\Patient;
use App\Models\Study;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\Models\User;
use DateTime;
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
        $answer = $this->json('GET', '/api/patients/12345671234567?role=Supervisor');
        $answer->assertStatus(200);

        $expectedKeys = ["code",
                        "firstName",
                        "lastName",
                        "gender",
                        "birthDay",
                        "birthMonth",
                        "birthYear",
                        "registrationDate",
                        "investigatorName",
                        "centerCode",
                        "centerName",
                        "countryCode",
                        "studyName",
                        "inclusionStatus",
                        "withdrawReason",
                        "withdrawDate"];

        $answer->assertJsonStructure($expectedKeys);



    }

    public function testGetPatientFailNotSupervisor(){
        $this->json('GET', '/api/patients/12345671234567?role=Supervisor')->assertStatus(403);
    }


    public function testGetPatientReviewerShouldNotContainPatientCenter() {

        AuthorizationTools::addRoleToUser(1, Constants::ROLE_REVIEWER, $this->study->name);

        //Test get patient 4
        $response = $this->json('GET', '/api/patients/12345671234567?role=Reviewer');
        $response->assertSuccessful();

        $answer = $response->content();
        $answer = json_decode($answer, true);

        //centerCode should be hidden and center details not in payload
        $this->assertNull($answer['centerCode']);
        $this->assertArrayNotHasKey('centerName',$answer);
        $this->assertArrayNotHasKey('countryCode', $answer);

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
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, $this->study->name);
        $patient = factory(Patient::class)->create(['center_code'=>0, 'study_name'=>$this->study->name]);

        $payload = [
            'firstname'=> 'a',
            'lastname'=>'b',
            'gender' => 'M',
            'birthDay'=> 5,
            'birthMonth'=> 12,
            'birthYear'=> 1955,
            'registrationDate'=>'12/31/2020',
            'investigatorName'=> 'salim',
            'centerCode'=>0
        ];

        $this->json('PATCH', '/api/patients/'.$patient->code, $payload)->assertStatus(200);
        //Check updated record in database
        $updatedPatientEntity = Patient::find($patient->code)->toArray();
        $this->assertEquals($payload['firstname'], $updatedPatientEntity['firstname']);
        $this->assertEquals($payload['lastname'], $updatedPatientEntity['lastname']);
        $this->assertEquals($payload['gender'], $updatedPatientEntity['gender']);
        $this->assertEquals($payload['birthDay'], $updatedPatientEntity['birth_day']);
        $this->assertEquals($payload['birthMonth'], $updatedPatientEntity['birth_month']);
        $this->assertEquals($payload['birthYear'], $updatedPatientEntity['birth_year']);
        $this->assertEquals(new DateTime($payload['registrationDate']), new DateTime($updatedPatientEntity['registration_date']));
        $this->assertEquals($payload['investigatorName'], $updatedPatientEntity['investigator_name']);
        $this->assertEquals($payload['centerCode'], $updatedPatientEntity['center_code']);
    }

    public function testModifyPatientWrongData(){
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, $this->study->name);
        $patient = factory(Patient::class)->create(['center_code'=>0, 'study_name'=>$this->study->name]);

        $this->json('PATCH', '/api/patients/'.$patient->code, ['gender'=>'G'])->assertStatus(400);
        $this->json('PATCH', '/api/patients/'.$patient->code, ['birthDay'=>32])->assertStatus(400);
        $this->json('PATCH', '/api/patients/'.$patient->code, ['birthMonth'=>13])->assertStatus(400);
        $this->json('PATCH', '/api/patients/'.$patient->code, ['birthYear'=>5000])->assertStatus(400);
        $this->json('PATCH', '/api/patients/'.$patient->code, ['registrationDate'=>'31/01/2020'])->assertStatus(400);

    }

    public function testModifyPatientForbidenNotSupervisor(){
        $patient = factory(Patient::class)->create(['center_code'=>0, 'study_name'=>$this->study->name]);

        $this->json('PATCH', '/api/patients/'.$patient->code, ['gender'=>'M'])->assertStatus(403);

    }

    public function testModifyPatientInclusionStatus(){

        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, $this->study->name);
        $patient = factory(Patient::class)->create(['center_code'=>0, 'study_name'=>$this->study->name]);

        $payload = [
            'inclusionStatus' => Constants::PATIENT_INCLUSION_STATUS_WITHDRAWN,
            'withdrawDate'=> '12/31/2020',
            'withdrawReason'=> 'fed-up'
        ];

        $this->json('PATCH', '/api/patients/'.$patient->code.'/inclusion-status', $payload)->assertStatus(200);
        $updatedPatientEntity = Patient::find($patient->code)->toArray();
        $this->assertEquals(Constants::PATIENT_INCLUSION_STATUS_WITHDRAWN, $updatedPatientEntity['inclusion_status']);
        $this->assertEquals(new DateTime($payload['withdrawDate']), new DateTime($updatedPatientEntity['withdraw_date']));
        $this->assertEquals($payload['withdrawReason'], $updatedPatientEntity['withdraw_reason']);

    }

    public function testModifyPatientWithdrawForbiddenNoRole(){
        $patient = factory(Patient::class)->create(['center_code'=>0, 'study_name'=>$this->study->name]);

        $payload = [
            'inclusionStatus' => Constants::PATIENT_INCLUSION_STATUS_INCLUDED,
            'withdrawDate'=> '12/31/2020',
            'withdrawReason'=> 'fed-up'
        ];

        $this->json('PATCH', '/api/patients/'.$patient->code.'/inclusion-status', $payload)->assertStatus(403);

    }

    public function testModifyPatientRemoveWithdraw(){

        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, $this->study->name);
        $patient = factory(Patient::class)->create(['center_code'=>0, 'study_name'=>$this->study->name]);

        $payload = [
            'inclusionStatus' => Constants::PATIENT_INCLUSION_STATUS_INCLUDED
        ];

        $this->json('PATCH', '/api/patients/'.$patient->code.'/inclusion-status', $payload)->assertStatus(200);
        $updatedPatientEntity = Patient::find($patient->code)->toArray();
        $this->assertEquals(Constants::PATIENT_INCLUSION_STATUS_INCLUDED, $updatedPatientEntity['inclusion_status']);
        $this->assertNull($updatedPatientEntity['withdraw_date']);
        $this->assertNull($updatedPatientEntity['withdraw_reason']);

    }

}
