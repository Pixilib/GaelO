<?php

namespace Tests\Feature\TestStudy;

use Tests\TestCase;
use App\Models\Study;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class DeleteStudyTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function testDeleteStudy()
    {
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        $this->json('DELETE', '/api/studies/' . $study->name, ['reason' => 'study finished'])->assertSuccessful();
    }

    public function testDeleteStudyShouldFailNoReason()
    {
        AuthorizationTools::actAsAdmin(true);
        $study = Study::factory()->create();
        $this->json('DELETE', '/api/studies/' . $study->name)->assertStatus(400);
    }

    public function testReactivateStudy()
    {
        AuthorizationTools::actAsAdmin(true);
        $study =  Study::factory()->create();
        $studyName = $study->name;
        $study->delete();
        $payload = ['reason' => 'need new analysis'];
        $this->json('POST', '/api/studies/' . $studyName . '/activate', $payload)->assertNoContent(200);
    }

    public function testReactivateStudyShouldFailNoReason()
    {
        AuthorizationTools::actAsAdmin(true);
        $study =  Study::factory()->create();
        $studyName = $study->name;
        $study->delete();
        $payload = [];
        $this->json('POST', '/api/studies/' . $studyName . '/activate', $payload)->assertStatus(400);
    }

    public function testReactivateStudyForbiddenNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $study = Study::factory()->create();
        $study->delete();
        $payload = ['reason' => 'need new analysis'];
        $this->json('POST', '/api/studies/' . $study->name . '/activate', $payload)->assertStatus(403);
    }
}
