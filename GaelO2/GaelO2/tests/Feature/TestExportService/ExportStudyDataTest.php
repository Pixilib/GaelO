<?php

namespace Tests\Feature\TestExportService;

use App\GaelO\Constants\Constants;
use App\Models\Study;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\AuthorizationTools;

class ExportStudyDataTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');

    }

    protected function setUp() : void {
        parent::setUp();
        Artisan::call('passport:install');
        $this->study = Study::factory()->create();

    }

    public function testExportDb()
    {
        $userId = AuthorizationTools::actAsAdmin(true);
        AuthorizationTools::addRoleToUser($userId, Constants::ROLE_SUPERVISOR, $this->study->name);
        $response = $this->get('/api/studies/'.$this->study->name.'/export');
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
