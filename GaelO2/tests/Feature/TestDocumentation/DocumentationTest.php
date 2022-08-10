<?php

namespace Tests\Feature\TestDocumentation;

use App\GaelO\Adapters\FrameworkAdapter;
use App\Models\Documentation;
use App\GaelO\Constants\Constants;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Study;
use Tests\AuthorizationTools;

class DocumentationTest extends TestCase
{

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        $this->study = Study::factory()->create();

        $this->validPayload = [
            'name' => 'documentationTest',
            'version' => '1.1.0',
            'investigator' => true,
            'monitor' => true,
            'controller' => false,
            'reviewer' => false
        ];
    }

    public function testForbiddenWhenNotSupervisor()
    {
        AuthorizationTools::actAsAdmin(false);
        $response = $this->post('api/studies/' . $this->study->name . '/documentations', $this->validPayload);
        $response->assertStatus(403);
    }


    public function testCreateDocumentation()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $response = $this->post('api/studies/' . $this->study->name . '/documentations', $this->validPayload);
        $response->assertStatus(201);
        $response->assertJsonStructure(['id']);
    }

    public function testUploadDocumentation()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $documentation = Documentation::factory()->studyName($this->study->name)->create();
        $response = $this->post('api/documentations/' . $documentation['id'] . '/file', ["binaryData" => base64_encode("testFileContent")], ['CONTENT_TYPE' => 'application/pdf']);
        $response->assertStatus(201);
    }

    public function testUploadDocumentationShouldFailBecauseWrongMime()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $documentation = Documentation::factory()->studyName($this->study->name)->create();
        $response = $this->post('api/documentations/' . $documentation['id'] . '/file', ["binaryData" => base64_encode("testFileContent")]);
        $response->assertStatus(400);
    }

    public function testUploadDocumentationShouldFailBecauseNotBase64Encoded()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $documentation = Documentation::factory()->studyName($this->study->name)->create();
        $response = $this->post('api/documentations/' . $documentation['id'] . '/file', ["binaryData" => "testFileContent"]);
        $response->assertStatus(400);
    }

    public function testDeleteDocumentationShouldFailBecauseNotSupervisor()
    {
        AuthorizationTools::actAsAdmin(false);
        $documentation = Documentation::factory()->studyName($this->study->name)->create();
        $response = $this->delete('api/documentations/' . $documentation['id']);
        $response->assertStatus(403);
    }

    public function testDeleteDocumentation()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $documentation = Documentation::factory()->studyName($this->study->name)->create();
        $response = $this->delete('api/documentations/' . $documentation['id']);
        $response->assertStatus(200);
    }

    public function testGetDocumentation()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        Documentation::factory()->studyName($this->study->name)->count(3)->create();
        $response = $this->get('api/studies/' . $this->study->name . '/documentations?role=Supervisor');
        $answerArray = json_decode($response->content(), true);
        $response->assertStatus(200);
        $this->assertEquals(3, sizeof($answerArray));
    }

    public function testGetDocumentationFailBecauseNotHavingRole()
    {
        AuthorizationTools::actAsAdmin(false);
        Documentation::factory()->studyName($this->study->name)->count(3)->create();
        $response = $this->get('api/studies/' . $this->study->name . '/documentations?role=Supervisor');
        $response->assertStatus(403);
    }

    public function testGetDocumentationOnlyInvestigator()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);
        Documentation::factory()->studyName($this->study->name)->investigator()->count(2)->create();
        Documentation::factory()->studyName($this->study->name)->count(5)->create();

        $response = $this->get('api/studies/' . $this->study->name . '/documentations?role=Investigator');
        $answerArray = json_decode($response->content(), true);
        $response->assertStatus(200);
        $this->assertEquals(2, sizeof($answerArray));
    }

    public function testGetDocumentationFile()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);
        FrameworkAdapter::storeFile($this->study->name . '/documentations/test.pdf', 'content');
        $documentation = Documentation::factory()->studyName($this->study->name)->investigator()->path($this->study->name.'/documentations/test.pdf')->create();
        $response = $this->get('api/documentations/' . $documentation->id . '/file');
        $response->assertStatus(200);
    }

    public function testGetDocumentationFileShouldFailedBecauseNotAllowed()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $documentation = Documentation::factory()->studyName($this->study->name)->path('/'.$this->study->name.'/documentations/test.pdf')->create();
        $response = $this->get('api/documentations/' . $documentation->id . '/file');
        $response->assertStatus(403);
    }



    public function testGetDocumentationFileShouldPassBecauseSupervisor()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        FrameworkAdapter::storeFile($this->study->name . '/documentations/test.pdf', 'content');
        $documentation = Documentation::factory()->studyName($this->study->name)->path('/'.$this->study->name.'/documentations/test.pdf')->create();
        $response = $this->get('api/documentations/' . $documentation->id . '/file');
        $response->assertStatus(200);
    }

    public function testModifyDocumentation()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $documentation = Documentation::factory()->studyName($this->study->name)->create();
        $newPayload = [
            'version' => '2.2.0',
            'controller' => true,
            'investigator' => true,
            'monitor' => true,
            'reviewer' => true
        ];

        $response = $this->patch('api/documentations/' . $documentation->id, $newPayload);
        $response->assertStatus(200);
    }

    public function testModifyDocumentationNotSemanticVersioning()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $documentation = Documentation::factory()->studyName($this->study->name)->create();
        $newPayload = [
            'version' => '2.2',
            'controller' => true,
            'investigator' => true,
            'monitor' => true,
            'reviewer' => true
        ];

        $response = $this->patch('api/documentations/' . $documentation->id, $newPayload);
        $response->assertStatus(400);
    }

    public function testModifyDocumentationConflict()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        Documentation::factory()->studyName($this->study->name)->name('newFile')->version('2.2.0')->create();
        $documentation = Documentation::factory()->studyName($this->study->name)->name('newFile')->version('1.0')->create();
        $newPayload = [
            'version' => '2.2.0',
            'controller' => true,
            'investigator' => true,
            'monitor' => true,
            'reviewer' => true
        ];
        $response = $this->patch('api/documentations/' . $documentation->id, $newPayload);
        $response->assertStatus(409);
    }

    public function testModifyDocumentationShouldFailNotSupervisor()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $documentation = Documentation::factory()->studyName($this->study->name)->create();
        $newPayload = [
            'version' => '2.0',
            'controller' => true,
            'investigator' => true,
            'monitor' => true,
            'reviewer' => true
        ];
        $response = $this->patch('api/documentations/' . $documentation->id, $newPayload);
        $response->assertStatus(403);
    }

    public function testReactivateDocumentation()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $documentation = Documentation::factory()->studyName($this->study->name)->create();

        $response = $this->patch('api/documentations/' . $documentation->id . '/reactivate');
        $response->assertStatus(200);
    }

    public function testReactivateDocumentationShouldFailNoSupervisor()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $documentation = Documentation::factory()->studyName($this->study->name)->create();

        $response = $this->patch('api/documentations/' . $documentation->id . '/reactivate');
        $response->assertStatus(403);
    }
}
