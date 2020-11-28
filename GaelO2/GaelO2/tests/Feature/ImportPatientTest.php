<?php

namespace Tests\Feature;

use App\GaelO\Constants\Constants;
use App\Study;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;
use Tests\AuthorizationTools;

class ImportPatientTest extends TestCase
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
        factory(Study::class, 1)->create(['name'=> 'test', 'patient_code_prefix' => 1234]);

        $this->validPayload = [ ["code" => 12341231234123,
        "lastName" => "test",
        "firstName" => "test",
        "gender" => "M",
        "birthDay" => 1,
        "birthMonth" => 1,
        "birthYear" => 1998,
        "studyName" => "test",
        "registrationDate" => '10/19/2020',
        "investigatorName" => "administrator",
        "centerCode" => 0,
        "inclusionStatus"  => Constants::PATIENT_INCLUSION_STATUS_INCLUDED,
        "withdrawReason" => null,
        "withdrawDate" => null]];

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );
    }



    public function testImportMultiplePatients() {

        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, 'test');
        $this->validPayload = [ ["code" => 12341231234123,
        "lastName" => "test",
        "firstName" => "test",
        "gender" => "M",
        "birthDay" => 1,
        "birthMonth" => 1,
        "birthYear" => 1998,
        "studyName" => "test",
        "registrationDate" => '10/19/2020',
        "investigatorName" => "administrator",
        "centerCode" => 0],
        ["code" => 12341231234124,
        "lastName" => "test",
        "firstName" => "test",
        "gender" => "M",
        "birthDay" => 1,
        "birthMonth" => 1,
        "birthYear" => 1998,
        "studyName" => "test",
        "registrationDate" => '10/19/2020',
        "investigatorName" => "administrator",
        "centerCode" => 0],
        ["code" => 12341231234125,
        "lastName" => "test",
        "firstName" => "test",
        "gender" => "M",
        "birthDay" => 1,
        "birthMonth" => 1,
        "birthYear" => 1998,
        "studyName" => "test",
        "registrationDate" => '10/19/2020',
        "investigatorName" => "administrator",
        "centerCode" => 0]
    ];
        $resp = $this->json('POST', '/api/studies/test/import-patients', $this->validPayload)->assertSuccessful();
        $this->assertEquals(3,sizeof($resp['success']));
        $this->assertEquals(0,sizeof($resp['fail']));
    }

    public function testImportPatient() {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, 'test');
        //Test patient creation
        $reponse1 = $this->json('POST', '/api/studies/test/import-patients', $this->validPayload)->assertSuccessful();
        $this->assertEquals(1,sizeof($reponse1['success']));
        $this->assertEquals(0,sizeof($reponse1['fail']));

        //Test that copies of existing patients don't insert
        $response2 = $this->json('POST', '/api/studies/test/import-patients', $this->validPayload);
        $this->assertEquals(0,sizeof($response2['success']));
        $this->assertEquals(1,sizeof($response2['fail']));
    }

    public function testImportPatientForbiddenNoRole(){
        $this->json('POST', '/api/studies/test/import-patients', $this->validPayload)->assertStatus(403);
    }

    public function testCreateWrongDayOfBirth() {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, 'test');
        $this->validPayload[0]['birthDay'] = 0;
        $resp = $this->json('POST', '/api/studies/test/import-patients', $this->validPayload);
        //Check that inserting patient failed because day of birth was incorrect
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Incorrect Birthdate day format']);
        $this->assertEquals(12341231234123, $resp['fail']['Incorrect Birthdate day format'][0]);

        $this->validPayload[0]['birthDay'] = 32;
        $resp = $this->json('POST', '/api/studies/test/import-patients', $this->validPayload);
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Incorrect Birthdate day format']);
        $this->assertEquals(12341231234123, $resp['fail']['Incorrect Birthdate day format'][0]);
    }

    public function testCreateWrongMonthOfBirth() {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, 'test');
        $this->validPayload[0]['birthMonth'] = 0;
        $resp = $this->json('POST', '/api/studies/test/import-patients', $this->validPayload);
        //Check that inserting patient failed because day of birth was incorrect
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Incorrect Birthdate month format']);
        $this->assertEquals(12341231234123, $resp['fail']['Incorrect Birthdate month format'][0]);

        $this->validPayload[0]['birthMonth'] = 13;
        $resp = $this->json('POST', '/api/studies/test/import-patients', $this->validPayload);
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Incorrect Birthdate month format']);
        $this->assertEquals(12341231234123, $resp['fail']['Incorrect Birthdate month format'][0]);
    }

    public function testCreateWrongYearOfBirth() {
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, 'test');
        $this->validPayload[0]['birthYear'] = 1800;
        $resp = $this->json('POST', '/api/studies/test/import-patients', $this->validPayload);
        //Check that inserting patient failed because day of birth was incorrect
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Incorrect Birthdate year format']);
        $this->assertEquals(12341231234123, $resp['fail']['Incorrect Birthdate year format'][0]);

        $this->validPayload[0]['birthYear'] = 3010;
        $resp = $this->json('POST', '/api/studies/test/import-patients', $this->validPayload);
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Incorrect Birthdate year format']);
        $this->assertEquals(12341231234123, $resp['fail']['Incorrect Birthdate year format'][0]);
    }

    public function testCreateAlreadyKnownPatient(){
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, 'test');
        $this->json('POST', '/api/studies/test/import-patients', $this->validPayload);
        $resp = $this->json('POST', '/api/studies/test/import-patients', $this->validPayload);
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Existing Patient Code']);
        $this->assertEquals(12341231234123, $resp['fail']['Existing Patient Code'][0]);
    }

    public function testIncorrectPatientCodeLength(){
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, 'test');
        $this->validPayload[0]['code'] = 123;
        $resp = $this->json('POST', '/api/studies/test/import-patients', $this->validPayload);
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Incorrect Patient Code Length']);
        $this->assertEquals(123, $resp['fail']['Incorrect Patient Code Length'][0]);
    }

    public function testIncorrectPatientPrefix(){
        AuthorizationTools::addRoleToUser(1, Constants::ROLE_SUPERVISOR, 'test');
        $this->validPayload[0]['code'] = 12431234123412;
        $resp = $this->json('POST', '/api/studies/test/import-patients', $this->validPayload);
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Wrong Patient Prefix']);
        $this->assertEquals(12431234123412, $resp['fail']['Wrong Patient Prefix'][0]);
    }
}
