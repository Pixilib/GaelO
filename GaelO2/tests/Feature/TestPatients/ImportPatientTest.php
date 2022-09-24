<?php

namespace Tests\Feature\TestPatients;

use App\GaelO\Constants\Constants;
use App\Models\Patient;
use App\Models\Study;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\AuthorizationTools;

class ImportPatientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp() : void{
        parent::setUp();
        $this->artisan('db:seed');
        $this->study = Study::factory()->patientCodeLength(14)->code('123')->create();

        $this->validPayload = [ ["code" => '12341231234123',
        "lastname" => "test",
        "firstname" => "test",
        "gender" => "M",
        "birthDay" => 1,
        "birthMonth" => 1,
        "birthYear" => 1998,
        "registrationDate" => '2011-10-05',
        "investigatorName" => "administrator",
        "centerCode" => 0,
        "inclusionStatus"  => Constants::PATIENT_INCLUSION_STATUS_INCLUDED
        ]];

    }



    public function testImportMultiplePatients() {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $this->validPayload = [ ["code" => '12341231234123',
        "lastname" => "test",
        "firstname" => "test",
        "gender" => "M",
        "birthDay" => 1,
        "birthMonth" => 1,
        "birthYear" => 1998,
        "registrationDate" => '2011-10-05',
        "investigatorName" => "administrator",
        "centerCode" => 0,
        "inclusionStatus"  => Constants::PATIENT_INCLUSION_STATUS_INCLUDED],
        ["code" => '12341231234124',
        "lastname" => "test",
        "firstname" => "test",
        "gender" => "M",
        "birthDay" => 1,
        "birthMonth" => 1,
        "birthYear" => 1998,
        "registrationDate" => '2011-10-06',
        "investigatorName" => "administrator",
        "centerCode" => 0,
        "inclusionStatus"  => Constants::PATIENT_INCLUSION_STATUS_INCLUDED],
        ["code" => '12341231234125',
        "lastname" => "test",
        "firstname" => "test",
        "gender" => "M",
        "birthDay" => 1,
        "birthMonth" => 1,
        "birthYear" => 1998,
        "registrationDate" => '2011-10-07',
        "investigatorName" => "administrator",
        "centerCode" => 0,
        "inclusionStatus"  => Constants::PATIENT_INCLUSION_STATUS_INCLUDED]
    ];
        $resp = $this->json('POST', '/api/studies/'.$this->study->name.'/import-patients', $this->validPayload);
        $resp->assertSuccessful();
        $this->assertEquals(3,sizeof($resp['success']));
        $this->assertEquals(0,sizeof($resp['fail']));

        $patient1 = Patient::find($this->study->code.'12341231234123')->toArray();
        $this->assertEquals('2011-10-05T00:00:00.000000Z', $patient1['registration_date']);
    }

    public function testImportPatient() {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        //Test patient creation
        $reponse1 = $this->json('POST', '/api/studies/'.$this->study->name.'/import-patients', $this->validPayload)->assertSuccessful();
        $this->assertEquals(1,sizeof($reponse1['success']));
        $this->assertEquals(0,sizeof($reponse1['fail']));

        //Test that copies of existing patients don't insert
        $response2 = $this->json('POST', '/api/studies/'.$this->study->name.'/import-patients', $this->validPayload);
        $this->assertEquals(0,sizeof($response2['success']));
        $this->assertEquals(1,sizeof($response2['fail']));
    }

    public function testImportPatientForbiddenNoRole(){
        AuthorizationTools::actAsAdmin(false);
        $this->json('POST', '/api/studies/'.$this->study->name.'/import-patients', $this->validPayload)->assertStatus(403);
    }

    public function testImportPatientForbiddenForAncillaryStudy(){
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        $ancillaryStudies = Study::factory()->ancillaryOf($this->study->name)->create();
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $ancillaryStudies->name);
        $answer = $this->json('POST', '/api/studies/'.$ancillaryStudies->name.'/import-patients', $this->validPayload);
        $answer->assertStatus(403);
    }

    public function testCreateWrongDayOfBirth() {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $this->validPayload[0]['birthDay'] = 0;
        $resp = $this->json('POST', '/api/studies/'.$this->study->name.'/import-patients', $this->validPayload);
        //Check that inserting patient failed because day of birth was incorrect
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Incorrect Birthdate day format']);
        $this->assertEquals(12341231234123, $resp['fail']['Incorrect Birthdate day format'][0]);

        $this->validPayload[0]['birthDay'] = 32;
        $resp = $this->json('POST', '/api/studies/'.$this->study->name.'/import-patients', $this->validPayload);
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Incorrect Birthdate day format']);
        $this->assertEquals(12341231234123, $resp['fail']['Incorrect Birthdate day format'][0]);
    }

    public function testCreateWrongMonthOfBirth() {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $this->validPayload[0]['birthMonth'] = 0;
        $resp = $this->json('POST', '/api/studies/'.$this->study->name.'/import-patients', $this->validPayload);
        //Check that inserting patient failed because day of birth was incorrect
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Incorrect Birthdate month format']);
        $this->assertEquals(12341231234123, $resp['fail']['Incorrect Birthdate month format'][0]);

        $this->validPayload[0]['birthMonth'] = 13;
        $resp = $this->json('POST', '/api/studies/'.$this->study->name.'/import-patients', $this->validPayload);
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Incorrect Birthdate month format']);
        $this->assertEquals(12341231234123, $resp['fail']['Incorrect Birthdate month format'][0]);
    }

    public function testCreateWrongYearOfBirth() {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $this->validPayload[0]['birthYear'] = 1800;
        $resp = $this->json('POST', '/api/studies/'.$this->study->name.'/import-patients', $this->validPayload);
        //Check that inserting patient failed because day of birth was incorrect
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Incorrect Birthdate year format']);
        $this->assertEquals(12341231234123, $resp['fail']['Incorrect Birthdate year format'][0]);

        $this->validPayload[0]['birthYear'] = 3010;
        $resp = $this->json('POST', '/api/studies/'.$this->study->name.'/import-patients', $this->validPayload);
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Incorrect Birthdate year format']);
        $this->assertEquals(12341231234123, $resp['fail']['Incorrect Birthdate year format'][0]);
    }

    public function testCreateAlreadyKnownPatient(){
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $this->json('POST', '/api/studies/'.$this->study->name.'/import-patients', $this->validPayload);
        $resp = $this->json('POST', '/api/studies/'.$this->study->name.'/import-patients', $this->validPayload);
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Existing Patient Code']);
        $this->assertEquals(12341231234123, $resp['fail']['Existing Patient Code'][0]);
    }

    public function testIncorrectPatientCodeLength(){
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $this->validPayload[0]['code'] = '123';
        $resp = $this->json('POST', '/api/studies/'.$this->study->name.'/import-patients', $this->validPayload);
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Incorrect Patient Code Length']);
        $this->assertEquals(123, $resp['fail']['Incorrect Patient Code Length'][0]);
    }

    public function testMissingInclusionDateWhileIncluded(){
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);

        $this->validPayload[0]['registrationDate'] = '123';
        $resp = $this->json('POST', '/api/studies/'.$this->study->name.'/import-patients', $this->validPayload);
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Registration Date Missing or Invalid']);
        $this->assertEquals(12341231234123, $resp['fail']['Registration Date Missing or Invalid'][0]);
    }


    public function testMissingInclusionDateAllowedIfPreIncluded(){
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $this->validPayload[0]['registrationDate'] = null;
        $this->validPayload[0]['inclusionStatus'] = Constants::PATIENT_INCLUSION_STATUS_PRE_INCLUDED;
        $resp = $this->json('POST', '/api/studies/'.$this->study->name.'/import-patients', $this->validPayload);
        $this->assertEquals(1, count($resp['success']));
    }

}
