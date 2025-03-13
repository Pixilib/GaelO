<?php

namespace Tests\Feature\TestDocumentation;

use App\GaelO\Adapters\FrameworkAdapter;
use App\Models\Documentation;
use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\TrackerRepository;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Study;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\AuthorizationTools;

class DocumentationTest extends TestCase
{

    use RefreshDatabase;
    private Study $study;
    private array $validPayload;
    private MockInterface $trackerSpy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
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

        $this->trackerSpy = $this->spy(TrackerRepository::class);
        app()->instance(TrackerRepository::class, $this->trackerSpy);
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
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::ROLE_SUPERVISOR, Mockery::any(), Mockery::any(), Constants::TRACKER_ADD_DOCUMENTATION, Mockery::any());
    }

    public function testUploadDocumentation()
    {
        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $documentation = Documentation::factory()->studyName($this->study->name)->create();
        $response = $this->post('api/documentations/' . $documentation['id'] . '/file', ["binaryData" => base64_encode("testFileContent")], ['CONTENT_TYPE' => 'application/pdf']);
        $response->assertStatus(201);
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::ROLE_SUPERVISOR, Mockery::any(), Mockery::any(), Constants::TRACKER_UPLOAD_DOCUMENTATION, Mockery::any());
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
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::ROLE_SUPERVISOR, Mockery::any(), Mockery::any(), Constants::TRACKER_DELETE_DOCUMENTATION, Mockery::any());
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
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::ROLE_SUPERVISOR, Mockery::any(), Mockery::any(), Constants::TRACKER_UPDATE_DOCUMENTATION, Mockery::any());
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

        $response = $this->post('api/documentations/' . $documentation->id . '/activate');
        $response->assertStatus(200);
        $this->trackerSpy->shouldHaveReceived('writeAction')->once()->with($currentUserId, Constants::ROLE_SUPERVISOR, Mockery::any(), Mockery::any(), Constants::TRACKER_REACTIVATE_DOCUMENTATION, Mockery::any());
    }

    public function testReactivateDocumentationShouldFailNoSupervisor()
    {

        $currentUserId = AuthorizationTools::actAsAdmin(false);
        AuthorizationTools::addRoleToUser($currentUserId, Constants::ROLE_INVESTIGATOR, $this->study->name);
        $documentation = Documentation::factory()->studyName($this->study->name)->create();

        $response = $this->post('api/documentations/' . $documentation->id . '/activate');
        $response->assertStatus(403);
    }
}
