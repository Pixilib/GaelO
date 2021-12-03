<?php

namespace Tests\Feature\TestUser;

use App\GaelO\Constants\Constants;
use App\Models\Center;
use App\Models\Patient;
use App\Models\Study;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Tests\AuthorizationTools;

class CenterTest extends TestCase
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


    public function testGetCenters()
    {
        AuthorizationTools::actAsAdmin(true);
        $response = $this->json('GET', '/api/centers')->content();
        $answer = json_decode($response, true);
        $this->assertEquals(1, sizeof($answer));
    }

    public function testGetCentersShouldFailNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $this->json('GET', '/api/centers')->assertStatus(403);
    }

    public function testGetCentersShouldFailNotSupervisor()
    {
        $this->study = Study::factory()->patientCodeLength(14)->code('123')->create();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $this->json('GET', '/api/centers?studyName='.$this->study->name)->assertStatus(403);
    }

    public function testGetCenter()
    {
        AuthorizationTools::actAsAdmin(true);
        $response = $this->json('GET', '/api/centers/0')->assertStatus(200)->content();
        $answer = json_decode($response, true);
        $this->assertArrayHasKey('code', $answer);
        $this->assertArrayHasKey('name', $answer);
        $this->assertArrayHasKey('countryCode', $answer);
    }

    public function testGetCenterShouldFailNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $this->json('GET', '/api/centers/0')->assertStatus(403);
    }

    public function testGetCenterShouldFailNotSupervisor()
    {
        $this->study = Study::factory()->patientCodeLength(14)->code('123')->create();
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $this->json('GET', '/api/centers/0?studyName='.$this->study->name)->assertStatus(403);
    }

    public function testAddCenter()
    {
        AuthorizationTools::actAsAdmin(true);
        $payload = [
            'name' => 'Paris',
            'code' => 8,
            'countryCode' => 'US'

        ];
        $this->json('POST', '/api/centers', $payload)->assertStatus(201);
    }

    public function testAddCenterShouldFailNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $payload = [
            'name' => 'Paris',
            'code' => 8,
            'countryCode' => 'US'

        ];
        $this->json('POST', '/api/centers', $payload)->assertStatus(403);
    }

    public function testAddCenterExistingCode()
    {
        AuthorizationTools::actAsAdmin(true);
        Center::factory()->code(8)->create();

        $payload = [
            'name' => 'Toulouse',
            'code' => 8,
            'countryCode' => 'US'
        ];
        $answer = $this->json('POST', '/api/centers', $payload);
        $answer->assertStatus(409);
        $answer->assertJsonStructure(["errorMessage"]);
    }

    public function testAddCenterExistingName()
    {
        AuthorizationTools::actAsAdmin(true);
        Center::factory()->code(8)->name('Paris')->create();

        $payload = [
            'name' => 'Paris',
            'code' => 9,
            'countryCode' => 'US'
        ];
        $answer = $this->json('POST', '/api/centers', $payload);
        $answer->assertStatus(409);
        $answer->assertJsonStructure(['errorMessage']);
    }

    public function testModifyCenterName()
    {
        AuthorizationTools::actAsAdmin(true);
        $payload = [
            'name' => 'newCenter',
        ];
        $answer = $this->json('PATCH', '/api/centers/0', $payload);
        $answer->assertStatus(200);
    }

    public function testModifyCenterCountryCode()
    {
        AuthorizationTools::actAsAdmin(true);
        $payload = [
            'countryCode' => 'US',
        ];
        $answer = $this->json('PATCH', '/api/centers/0', $payload);
        $answer->assertStatus(200);
    }

    public function testModifyCenterShouldFailNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $payload = [
            'name' => 'newCenter',
            'countryCode' => 'FR'

        ];
        $answer = $this->json('PATCH', '/api/centers/0', $payload);
        $answer->assertStatus(403);
    }

    public function testModifyCenterNotExisting()
    {
        AuthorizationTools::actAsAdmin(true);
        $payload = [
            'name' => 'newCenter',
            'countryCode' => 'FR'
        ];
        //Non existing center modification should fail
        $this->json('PATCH', '/api/centers/1', $payload)->assertStatus(404);
    }

    public function testModifyCenterExistingName()
    {
        AuthorizationTools::actAsAdmin(true);

        Center::factory()->code(8)->name('Paris')->create();
        Center::factory()->code(9)->name('Toulouse')->create();

        $payload = [
            'name' => 'Toulouse',
            'countryCode' => 'US'
        ];
        $this->json('PATCH', '/api/centers/8', $payload)->assertStatus(409);
    }

    public function testGetCentersFromStudy(){
        $study = Study::factory()->name('TEST')->create();
        $center = Center::factory()->code(1)->create();
        $patient = Patient::factory()->studyName($study->name)->centerCode($center->code)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $study->name);

        $this->get('api/studies/'.$study->name.'/centers')->assertSuccessful();
    }

    public function testGetCentersFromStudyShouldFailNotSupervisor(){
        $study = Study::factory()->name('TEST')->create();
        $center = Center::factory()->code(1)->create();
        $patient = Patient::factory()->studyName($study->name)->centerCode($center->code)->create();

        $currentUserId = AuthorizationTools::actAsAdmin(false);

        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $study->name);

        $this->get('api/studies/'.$study->name.'/centers')->assertStatus(403);
    }
}
