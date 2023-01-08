<?php

namespace Tests\Feature\TestExportService;

use App\GaelO\Constants\Constants;
use App\Models\Study;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AuthorizationTools;

class ExportStudyDataTest extends TestCase
{
    use RefreshDatabase;
    private Study $study;

    protected function setUp() : void {
        parent::setUp();
        $this->artisan('db:seed');
        $this->study = Study::factory()->create();
    }

    public function testExportDb()
    {
        $userId = AuthorizationTools::actAsAdmin(true);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $response = $this->get('/api/studies/'.$this->study->name.'/export');
        ob_start();
        $response->sendContent();
        ob_flush();
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/zip');
    }

    public function testExportDbShouldBeForbiddenNotAdmin()
    {
        AuthorizationTools::actAsAdmin(false);
        $response = $this->get('/api/studies/'.$this->study->name.'/export');
        $response->assertStatus(403);
    }
}
