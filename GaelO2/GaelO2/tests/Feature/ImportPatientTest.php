<?php

namespace Tests\Feature;

use App\Study;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\User;

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
        "withdraw" => false,
        "withdrawReason" => null,
        "withdrawDate" => null]];

        Artisan::call('passport:install');
        Passport::actingAs(
            User::where('id',1)->first()
        );
    }

    public function testImportMultiplePatients() {
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
        "withdraw" => false,
        "withdrawReason" => null,
        "withdrawDate" => null],
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
        "centerCode" => 0,
        "withdraw" => false,
        "withdrawReason" => null,
        "withdrawDate" => null],
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
        "centerCode" => 0,
        "withdraw" => false,
        "withdrawReason" => null,
        "withdrawDate" => null]
    ];
        $resp = $this->json('POST', '/api/studies/test/import-patients', $this->validPayload)->assertSuccessful();
        $this->json('GET', '/api/patients')->assertJsonCount(3);
    }

    public function testImportPatient() {
        //Test patient creation
        $resp = $this->json('POST', '/api/studies/test/import-patients', $this->validPayload)->assertSuccessful();

        //Test that copies of existing patients don't insert
        $this->json('POST', '/api/studies/test/import-patients', $this->validPayload);
        $this->json('GET', '/api/patients/0')->assertJsonCount(1);

        //assertion on fails and successes
        //Use (and test) the GET api to get data of created patient and check it is returned
        $this->json('GET', '/api/patients/12341231234123')->assertJsonCount(14);
    }

    public function testCreateWrongDayOfBirth() {
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
        $this->json('POST', '/api/studies/test/import-patients', $this->validPayload);
        $resp = $this->json('POST', '/api/studies/test/import-patients', $this->validPayload);
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Existing Patient Code']);
        $this->assertEquals(12341231234123, $resp['fail']['Existing Patient Code'][0]);
    }

    public function testIncorrectPatientCodeLength(){
        $this->validPayload[0]['code'] = 123;
        $resp = $this->json('POST', '/api/studies/test/import-patients', $this->validPayload);
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Incorrect Patient Code Length']);
        $this->assertEquals(123, $resp['fail']['Incorrect Patient Code Length'][0]);
    }

    public function testIncorrectPatientPrefix(){
        $this->validPayload[0]['code'] = 12431234123412;
        $resp = $this->json('POST', '/api/studies/test/import-patients', $this->validPayload);
        $this->assertEquals(0, count($resp['success']));
        $this->assertNotEmpty($resp['fail']['Wrong Patient Prefix']);
        $this->assertEquals(12431234123412, $resp['fail']['Wrong Patient Prefix'][0]);
    }
}
